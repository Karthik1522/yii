<?php

Yii::import('application.modules.inventory.models.Product');
Yii::import('application.modules.inventory.models.Category');

class DefaultController extends Controller
{
    public $layout = 'application.modules.inventory.views.layouts.main';

    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    public function accessRules()
    {
        return array(

            array('allow',
                'actions' => array('index', 'stockLevel', 'productsPerCategory', 'lowStockReport', 'priceRangeReport'),
                'expression' => 'Yii::app()->user->hasRole(array("admin", "manager"))',
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionIndex()
    {
        Yii::log("Starting actionIndex", CLogger::LEVEL_INFO, 'application.report.default.index');

        try {
            Yii::log("Successfully loaded report index view", CLogger::LEVEL_INFO, 'application.report.default.index');

            $this->render('index');
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.index');
            throw new CHttpException(500, 'Error loading reports dashboard.');
        }
    }

    public function actionStockLevel()
    {
        Yii::log("Starting actionStockLevel", CLogger::LEVEL_INFO, 'application.report.default.stockLevel');

        try {
            $cacheKey = 'report_stock_level';
            $cachedResult = Yii::app()->cache->get($cacheKey);

            $reportData = [];
            $totalStockValue = 0;

            if ($cachedResult === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.stockLevel');

                try {
                    $aggregator = MongoAggregator::getInstance();
                    $productCollection = Product::model()->getCollection();
                    $aggregator->setCollection($productCollection);

                    $pipeline = [
                        [
                            '$project' => [
                                '_id' => 1,
                                'sku' => '$sku',
                                'name' => '$name',
                                'category_id' => '$category_id',
                                'quantity' => '$quantity',
                                'price' => '$price',
                                'stock_value' => ['$multiply' => ['$quantity', '$price']]
                            ]
                        ],
                        ['$sort' => ['name' => 1]]
                    ];

                    $aggregationResults = $aggregator->aggregate($pipeline);
                    $reportData = [];
                    $totalStockValue = 0;

                    if (isset($aggregationResults['ok']) && $aggregationResults['ok'] == 1.0 && isset($aggregationResults['result']) && is_array($aggregationResults['result'])) {

                        // Step 1: Load categories
                        $categoryModels = Category::model()->findAll();
                        $categoryMap = [];
                        foreach ($categoryModels as $cat) {
                            $categoryMap[(string) $cat->_id] = $cat->name;
                        }

                        // Step 2: Merge category name
                        foreach ($aggregationResults['result'] as $item) {
                            $categoryId = isset($item['category_id']) ? (string) $item['category_id'] : null;
                            $item['categoryName'] = isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId] : 'Unknown';

                            // Add to total
                            $totalStockValue += isset($item['stock_value']) ? (float)$item['stock_value'] : 0;

                            $reportData[] = $item;
                        }

                        Yii::app()->cache->set($cacheKey, ['data' => $reportData, 'totalValue' => $totalStockValue], 3600);
                        Yii::log("Stock level report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.default.stockLevel');
                    } else {
                        Yii::log("MongoAggregator Aggregation failed: " . CVarDumper::dumpAsString($aggregationResults), CLogger::LEVEL_WARNING, 'application.report.default.stockLevel');
                        Yii::app()->cache->set($cacheKey, ['data' => [], 'totalValue' => 0], 3600);
                    }

                } catch (Exception $e) {
                    Yii::log("Error during stock level aggregation: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), CLogger::LEVEL_ERROR, 'application.report.default.stockLevel');
                    Yii::app()->user->setFlash('error', 'Could not generate stock report due to a server error.');
                    Yii::app()->cache->set($cacheKey, ['data' => [], 'totalValue' => 0, 'error' => true], 600);
                }

            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.stockLevel');
                $reportData = isset($cachedResult['data']) ? $cachedResult['data'] : [];
                $totalStockValue = isset($cachedResult['totalValue']) ? $cachedResult['totalValue'] : 0;
                if (isset($cachedResult['error']) && $cachedResult['error']) {
                    Yii::app()->user->setFlash('error', 'Could not generate stock report (cached error).');
                }
            }

            $dataProvider = new CArrayDataProvider($reportData, array(
                'id' => 'stock-level-report',
                'keyField' => '_id',
                'sort' => array(
                    'attributes' => array(
                        '_id', 'sku', 'name', 'category_id', 'categoryName', 'quantity', 'price', 'stock_value'
                    ),
                    'defaultOrder' => array(
                        'name' => CSort::SORT_ASC,
                    ),
                ),
                'pagination' => array(
                    'pageSize' => 20,
                ),
            ));

            Yii::log("Successfully generated stock level report", CLogger::LEVEL_INFO, 'application.report.default.stockLevel');

            $this->render('stockLevel', array(
                'dataProvider' => $dataProvider,
                'totalStockValue' => $totalStockValue,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionStockLevel: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.stockLevel');
            throw new CHttpException(500, 'Error generating stock level report.');
        }
    }

    public function actionProductsPerCategory()
    {
        Yii::log("Starting actionProductsPerCategory", CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');

        try {
            $cacheKey = 'report_products_per_category_simple';
            $reportData = Yii::app()->cache->get($cacheKey);

            if ($reportData === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');

                try {
                    $aggregator = MongoAggregator::getInstance();
                    $productCollection = Product::model()->getCollection();
                    $aggregator->setCollection($productCollection);

                    // Step 1: Aggregate product counts per category
                    $pipeline = [
                        [
                            '$group' => [
                                '_id' => '$category_id',
                                'productCount' => ['$sum' => 1]
                            ]
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'categoryId' => '$_id',
                                'productCount' => 1
                            ]
                        ],
                        [
                            '$sort' => ['categoryId' => 1]
                        ]
                    ];

                    $results = $aggregator->aggregate($pipeline);
                    $reportData = [];

                    if (isset($results['ok']) && $results['ok'] == 1.0) {
                        $categoryModels = Category::model()->findAll();
                        $categoryMap = [];
                        foreach ($categoryModels as $cat) {
                            $categoryMap[(string) $cat->_id] = $cat->name;
                        }

                        foreach ($results['result'] as $row) {
                            $categoryId = (string) $row['categoryId'];
                            $row['categoryName'] = isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId] : 'Unknown';
                            $row['categoryId'] = $categoryId;
                            $reportData[] = $row;
                        }

                        Yii::app()->cache->set($cacheKey, $reportData, 3600);
                        Yii::log("Products per category report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');
                    } else {
                        $reportData = [];
                        Yii::log("ProductsPerCategory aggregation failed", CLogger::LEVEL_ERROR, 'application.report.default.productsPerCategory');
                    }
                } catch (Exception $e) {
                    $reportData = [];
                    Yii::log("Error in ProductsPerCategory aggregation: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.productsPerCategory');
                    Yii::app()->user->setFlash('error', 'Could not generate products per category report.');
                }
            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');
            }

            $dataProvider = new CArrayDataProvider($reportData, [
                'keyField' => 'categoryId',
                'sort' => ['attributes' => ['categoryId', 'productCount', 'categoryName']],
                'pagination' => ['pageSize' => 20],
            ]);

            Yii::log("Successfully generated products per category report", CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');

            $this->render('productsPerCategory', [
                'dataProvider' => $dataProvider,
            ]);
        } catch (Exception $e) {
            Yii::log("Error in actionProductsPerCategory: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.productsPerCategory');
            throw new CHttpException(500, 'Error generating products per category report.');
        }
    }

    public function actionPriceRangeReport()
    {
        Yii::log("Starting actionPriceRangeReport", CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

        try {
            $cacheKey = 'report_price_range';
            $reportData = Yii::app()->cache->get($cacheKey);

            if ($reportData === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

                try {
                    $aggregator = MongoAggregator::getInstance();
                    $productCollection = Product::model()->getCollection();
                    $aggregator->setCollection($productCollection);

                    // Define the $bucket stage as an array
                    $bucketStage = [
                        '$bucket' => [
                            'groupBy' => '$price',
                            'boundaries' => [0, 50, 100, 200, 500, 1000],
                            'default' => 'Over 1000',
                            'output' => [
                                'productCount' => ['$sum' => 1],
                                'totalStockValueInBucket' => ['$sum' => ['$multiply' => ['$quantity', '$price']]]
                            ]
                        ]
                    ];

                    $projectStage = [
                        '$project' => [
                            '_id' => 0,
                            'priceRange' => '$_id',
                            'productCount' => 1,
                            'totalStockValueInBucket' => 1
                        ]
                    ];

                    $sortStage = ['$sort' => ['priceRange' => 1]];

                    $pipeline = [];
                    $pipeline[] = $bucketStage;
                    $pipeline[] = $projectStage;
                    $pipeline[] = $sortStage;

                    $results = $aggregator->aggregate($pipeline);

                    if (isset($results['ok']) && $results['ok'] == 1.0 && isset($results['result'])) {
                        $reportData = $results['result'];
                        Yii::app()->cache->set($cacheKey, $reportData, 3600);
                        Yii::log("Price range report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');
                    } else {
                        $reportData = [];
                        Yii::log("PriceRangeReport aggregation failed: " . CVarDumper::dumpAsString($results), CLogger::LEVEL_ERROR, 'application.report.default.priceRangeReport');
                        Yii::app()->cache->set($cacheKey, [], 3600);
                    }
                } catch (Exception $e) {
                    $reportData = [];
                    Yii::log("Error in PriceRangeReport aggregation: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.priceRangeReport');
                    Yii::app()->user->setFlash('error', 'Could not generate price range report.');
                    Yii::app()->cache->set($cacheKey, ['error' => true], 600);
                }
            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

                $cachedResult = Yii::app()->cache->get($cacheKey);
                if (isset($cachedResult['error']) && $cachedResult['error']) {
                    $reportData = [];
                    Yii::app()->user->setFlash('error', 'Could not generate price range report (cached error).');
                } elseif (is_array($cachedResult)) {
                    $reportData = $cachedResult;
                } else {
                    $reportData = [];
                }
            }

            $dataProvider = new CArrayDataProvider($reportData, [
                'id' => 'price-range-report',
                'keyField' => 'priceRange',
                'sort' => ['attributes' => ['priceRange', 'productCount', 'totalStockValueInBucket']],
                'pagination' => ['pageSize' => 10],
            ]);

            Yii::log("Successfully generated price range report", CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

            $this->render('priceRangeReport', [
                'dataProvider' => $dataProvider,
            ]);
        } catch (Exception $e) {
            Yii::log("Error in actionPriceRangeReport: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.priceRangeReport');
            throw new CHttpException(500, 'Error generating price range report.');
        }
    }
}
