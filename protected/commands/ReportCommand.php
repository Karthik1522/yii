<?php

Yii::import('application.modules.inventory.models.*');
Yii::import('application.components.MongoAggregator');

class ReportCommand extends CConsoleCommand
{
    public function run($args)
    {
        echo "Starting low stock report generation...\n";
        
        // Default threshold if not provided
        $threshold = isset($args[0]) ? (int)$args[0] : 10;
        
        $this->generateAndEmailLowStockReport($threshold);
    }

    public function generateAndEmailLowStockReport($threshold = 10)
    {
        echo "Checking for products with stock below {$threshold}...\n";

        try {
            // Get MongoDB collection instance
            $collection = Product::model()->getCollection();
            
            if (!$collection) {
                throw new Exception("Failed to get MongoDB collection for products");
            }

            $pipeline = [
                ['$match' => ['quantity' => ['$lt' => $threshold]]],
                [
                    '$project' => [
                        '_id' => 1,
                        'name' => '$name',
                        'sku' => '$sku',
                        'quantity' => '$quantity'
                    ]
                ],
                ['$sort' => ['quantity' => 1]]
            ];

            echo "Executing aggregation pipeline...\n";
            $cursor = $collection->aggregate($pipeline);
            
            $lowStockProducts = iterator_to_array($cursor);
            
            if (empty($lowStockProducts)) {
                echo "No products found below the threshold.\n";
                return 0;
            }

            echo "Found " . count($lowStockProducts) . " low stock product(s).\n";

            // Prepare email
            $emailSubject = 'Inventory Alert: Low Stock Products - ' . date('Y-m-d H:i');
            $emailBodyHtml = $this->generateLowStockEmailHtml($lowStockProducts, $threshold);

            // Send email
            if (Yii::app()->hasComponent('mailer')) {
                $mailer = Yii::app()->mailer;
                $recipientEmail = 'arvapallikarthikeya@gmail.com';
                $recipientName = 'Inventory Manager';
                
                $sentCount = $mailer->send(
                    $recipientEmail,
                    $emailSubject,
                    $emailBodyHtml
                );

                if ($sentCount > 0) {
                    echo "Email sent successfully.\n";
                    return 0;
                } else {
                    echo "Failed to send email.\n";
                    return 1;
                }
            } else {
                echo "Mailer component not configured. Here's what would have been sent:\n";
                echo $emailBodyHtml . "\n";
                return 1;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            Yii::log("ReportCommand error: " . $e->getMessage(), CLogger::LEVEL_ERROR);
            return 1;
        }
    }

    protected function generateLowStockEmailHtml(array $lowStockProducts, $threshold)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Low Stock Alert</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { width: 90%; max-width: 700px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
                h1 { color: #d9534f; border-bottom: 2px solid #d9534f; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
                .low-stock { color: #d9534f; font-weight: bold; }
                .footer { margin-top: 30px; font-size: 0.9em; text-align: center; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Low Stock Alert</h1>
                <p>The following products have quantities below the threshold of <?php echo $threshold; ?>:</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Current Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $product): ?>
                        <tr>
                            <td><?php echo CHtml::encode($product['sku']); ?></td>
                            <td><?php echo CHtml::encode($product['name']); ?></td>
                            <td class="low-stock"><?php echo CHtml::encode($product['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>Please take appropriate action to restock these items.</p>
                
                <div class='footer'>
                    This is an automated message from your Inventory Management System.<br>
                    Generated on <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}