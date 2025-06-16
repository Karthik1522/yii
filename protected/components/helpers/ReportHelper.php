<?php

class ReportHelper
{
    /**
     * Generates stock level report data
     *
     * @return array Result array with 'success' boolean, 'data' array, 'totalValue' number, and optional 'message'
     */
    public static function generateStockLevelReport()
    {
        Yii::log("Generating stock level report", CLogger::LEVEL_INFO, 'application.report.helper.generateStockLevelReport');

        try {
            $cacheKey = 'report_stock_level';
            $cachedResult = Yii::app()->cache->get($cacheKey);

            $reportData = [];
            $totalStockValue = 0;

            if ($cachedResult === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generateStockLevelReport');

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

                    if (isset($aggregationResults['ok']) && $aggregationResults['ok'] == 1.0 && isset($aggregationResults['result']) && is_array($aggregationResults['result'])) {

                        // Load categories
                        $categoryModels = Category::model()->findAll();
                        $categoryMap = [];
                        foreach ($categoryModels as $cat) {
                            $categoryMap[(string) $cat->_id] = $cat->name;
                        }

                        // Merge category names and calculate total
                        foreach ($aggregationResults['result'] as $item) {
                            $categoryId = isset($item['category_id']) ? (string) $item['category_id'] : null;
                            $item['categoryName'] = isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId] : 'Unknown';
                            $totalStockValue += isset($item['stock_value']) ? (float)$item['stock_value'] : 0;
                            $reportData[] = $item;
                        }

                        Yii::app()->cache->set($cacheKey, ['data' => $reportData, 'totalValue' => $totalStockValue], 3600);
                        Yii::log("Stock level report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.helper.generateStockLevelReport');
                    } else {
                        Yii::log("MongoAggregator Aggregation failed: " . CVarDumper::dumpAsString($aggregationResults), CLogger::LEVEL_WARNING, 'application.report.helper.generateStockLevelReport');
                        Yii::app()->cache->set($cacheKey, ['data' => [], 'totalValue' => 0], 3600);
                    }

                } catch (Exception $e) {
                    Yii::log("Error during stock level aggregation: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), CLogger::LEVEL_ERROR, 'application.report.helper.generateStockLevelReport');
                    Yii::app()->cache->set($cacheKey, ['data' => [], 'totalValue' => 0, 'error' => true], 600);
                    return [
                        'success' => false,
                        'data' => [],
                        'totalValue' => 0,
                        'message' => 'Could not generate stock report due to a server error.'
                    ];
                }

            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generateStockLevelReport');
                $reportData = isset($cachedResult['data']) ? $cachedResult['data'] : [];
                $totalStockValue = isset($cachedResult['totalValue']) ? $cachedResult['totalValue'] : 0;

                if (isset($cachedResult['error']) && $cachedResult['error']) {
                    return [
                        'success' => false,
                        'data' => [],
                        'totalValue' => 0,
                        'message' => 'Could not generate stock report (cached error).'
                    ];
                }
            }

            return [
                'success' => true,
                'data' => $reportData,
                'totalValue' => $totalStockValue
            ];

        } catch (Exception $e) {
            Yii::log("Error in generateStockLevelReport: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.helper.generateStockLevelReport');
            return [
                'success' => false,
                'data' => [],
                'totalValue' => 0,
                'message' => 'Error generating stock level report.'
            ];
        }
    }

    /**
     * Generates products per category report data
     *
     * @return array Result array with 'success' boolean, 'data' array, and optional 'message'
     */
    public static function generateProductsPerCategoryReport()
    {
        Yii::log("Generating products per category report", CLogger::LEVEL_INFO, 'application.report.helper.generateProductsPerCategoryReport');

        try {
            $cacheKey = 'report_products_per_category_simple';
            $reportData = Yii::app()->cache->get($cacheKey);

            if ($reportData === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generateProductsPerCategoryReport');

                try {
                    $aggregator = MongoAggregator::getInstance();
                    $productCollection = Product::model()->getCollection();
                    $aggregator->setCollection($productCollection);

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

                    if (isset($results['ok']) && $results['ok'] == 1.0) {
                        $categoryModels = Category::model()->findAll();
                        $categoryMap = [];
                        foreach ($categoryModels as $cat) {
                            $categoryMap[(string) $cat->_id] = $cat->name;
                        }

                        $reportData = [];
                        foreach ($results['result'] as $row) {
                            $categoryId = (string) $row['categoryId'];
                            $row['categoryName'] = isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId] : 'Unknown';
                            $row['categoryId'] = $categoryId;
                            $reportData[] = $row;
                        }

                        Yii::app()->cache->set($cacheKey, $reportData, 3600);
                        Yii::log("Products per category report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.helper.generateProductsPerCategoryReport');
                    } else {
                        $reportData = [];
                        Yii::log("ProductsPerCategory aggregation failed", CLogger::LEVEL_ERROR, 'application.report.helper.generateProductsPerCategoryReport');
                    }
                } catch (Exception $e) {
                    $reportData = [];
                    Yii::log("Error in ProductsPerCategory aggregation: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.helper.generateProductsPerCategoryReport');
                    return [
                        'success' => false,
                        'data' => [],
                        'message' => 'Could not generate products per category report.'
                    ];
                }
            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generateProductsPerCategoryReport');
            }

            return [
                'success' => true,
                'data' => $reportData
            ];

        } catch (Exception $e) {
            Yii::log("Error in generateProductsPerCategoryReport: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.helper.generateProductsPerCategoryReport');
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error generating products per category report.'
            ];
        }
    }

    /**
     * Generates price range report data
     *
     * @return array Result array with 'success' boolean, 'data' array, and optional 'message'
     */
    public static function generatePriceRangeReport()
    {
        Yii::log("Generating price range report", CLogger::LEVEL_INFO, 'application.report.helper.generatePriceRangeReport');

        try {
            $cacheKey = 'report_price_range';
            $reportData = Yii::app()->cache->get($cacheKey);

            if ($reportData === false) {
                Yii::log("Cache miss for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generatePriceRangeReport');

                try {
                    $aggregator = MongoAggregator::getInstance();
                    $productCollection = Product::model()->getCollection();
                    $aggregator->setCollection($productCollection);

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

                    $pipeline = [$bucketStage, $projectStage, $sortStage];
                    $results = $aggregator->aggregate($pipeline);

                    if (isset($results['ok']) && $results['ok'] == 1.0 && isset($results['result'])) {
                        $reportData = $results['result'];
                        Yii::app()->cache->set($cacheKey, $reportData, 3600);
                        Yii::log("Price range report generated and cached successfully", CLogger::LEVEL_INFO, 'application.report.helper.generatePriceRangeReport');
                    } else {
                        $reportData = [];
                        Yii::log("PriceRangeReport aggregation failed: " . CVarDumper::dumpAsString($results), CLogger::LEVEL_ERROR, 'application.report.helper.generatePriceRangeReport');
                        Yii::app()->cache->set($cacheKey, [], 3600);
                    }
                } catch (Exception $e) {
                    $reportData = [];
                    Yii::log("Error in PriceRangeReport aggregation: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.helper.generatePriceRangeReport');
                    Yii::app()->cache->set($cacheKey, ['error' => true], 600);
                    return [
                        'success' => false,
                        'data' => [],
                        'message' => 'Could not generate price range report.'
                    ];
                }
            } else {
                Yii::log("Cache hit for: " . $cacheKey, CLogger::LEVEL_INFO, 'application.report.helper.generatePriceRangeReport');

                $cachedResult = Yii::app()->cache->get($cacheKey);
                if (isset($cachedResult['error']) && $cachedResult['error']) {
                    return [
                        'success' => false,
                        'data' => [],
                        'message' => 'Could not generate price range report (cached error).'
                    ];
                } elseif (is_array($cachedResult)) {
                    $reportData = $cachedResult;
                } else {
                    $reportData = [];
                }
            }

            return [
                'success' => true,
                'data' => $reportData
            ];

        } catch (Exception $e) {
            Yii::log("Error in generatePriceRangeReport: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.helper.generatePriceRangeReport');
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error generating price range report.'
            ];
        }
    }

    /**
     * Creates a data provider for report data
     *
     * @param array $data Report data
     * @param array $config Configuration for the data provider
     * @return CArrayDataProvider
     */
    public static function createReportDataProvider($data, $config = [])
    {
        $defaultConfig = [
            'keyField' => '_id',
            'sort' => ['attributes' => []],
            'pagination' => ['pageSize' => 20],
        ];

        $config = array_merge($defaultConfig, $config);

        return new CArrayDataProvider($data, $config);
    }

    /**
     * Handles flash messages for report operations
     *
     * @param array $result Result array from report generation
     */
    public static function handleReportFlashMessages($result)
    {
        if (!$result['success'] && isset($result['message'])) {
            Yii::app()->user->setFlash('error', $result['message']);
        }
    }
}
