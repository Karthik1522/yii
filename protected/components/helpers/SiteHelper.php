<?php

/**
 * SiteHelper - Business logic helper for Site operations
 *
 * This helper class extracts core business logic from SiteController
 * to improve testability, maintainability, and reusability across controllers.
 */
class SiteHelper
{
    /**
     * Processes user login with provided credentials
     *
     * @param array $loginData Login form data
     * @return array Result array with 'success' boolean, 'model' object, and optional 'redirectUrl'
     */
    public static function processLogin($loginData)
    {
        Yii::log("Processing user login", CLogger::LEVEL_INFO, 'application.site.helper.processLogin');

        try {
            $model = new LoginForm();
            $model->attributes = $loginData;

            if ($model->validate() && $model->login()) {
                Yii::log("Successfully logged in user", CLogger::LEVEL_INFO, 'application.site.helper.processLogin');
                return [
                    'success' => true,
                    'model' => $model,
                    'redirectUrl' => array('site/index')
                ];
            } else {
                Yii::log("Login failed for user", CLogger::LEVEL_WARNING, 'application.site.helper.processLogin');
                return [
                    'success' => false,
                    'model' => $model
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in processLogin: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.processLogin');
            throw new CHttpException(500, 'Error processing login.');
        }
    }

    /**
     * Processes user registration with provided data
     *
     * @param array $userData User registration form data
     * @return array Result array with 'success' boolean, 'model' object, and 'message' string
     */
    public static function processRegistration($userData)
    {
        Yii::log("Processing user registration", CLogger::LEVEL_INFO, 'application.site.helper.processRegistration');

        try {
            $model = new User('register');
            $model->attributes = $userData;

            if ($model->validate()) {
                if ($model->save()) {
                    Yii::log("Successfully registered new user", CLogger::LEVEL_INFO, 'application.site.helper.processRegistration');
                    return [
                        'success' => true,
                        'model' => $model,
                        'message' => 'Registration successful. You can now log in.',
                        'redirectUrl' => array('site/login')
                    ];
                } else {
                    Yii::log("Failed to save user during registration", CLogger::LEVEL_ERROR, 'application.site.helper.processRegistration');
                    return [
                        'success' => false,
                        'model' => $model,
                        'message' => 'Something went wrong while saving the user.'
                    ];
                }
            } else {
                Yii::log("User registration validation failed", CLogger::LEVEL_WARNING, 'application.site.helper.processRegistration');
                return [
                    'success' => false,
                    'model' => $model,
                    'message' => 'Please fix the errors in the form.'
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in processRegistration: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.processRegistration');
            throw new CHttpException(500, 'Error processing registration.');
        }
    }

    /**
     * Processes contact form submission
     *
     * @param array $contactData Contact form data
     * @return array Result array with 'success' boolean, 'model' object, and optional 'message'
     */
    public static function processContactForm($contactData)
    {
        Yii::log("Processing contact form", CLogger::LEVEL_INFO, 'application.site.helper.processContactForm');

        try {
            $model = new ContactForm();
            $model->attributes = $contactData;

            if ($model->validate()) {
                $result = self::sendContactEmail($model);
                if ($result['success']) {
                    Yii::log("Successfully sent contact email", CLogger::LEVEL_INFO, 'application.site.helper.processContactForm');
                    return [
                        'success' => true,
                        'model' => $model,
                        'message' => 'Thank you for contacting us. We will respond to you as soon as possible.'
                    ];
                } else {
                    Yii::log("Failed to send contact email", CLogger::LEVEL_ERROR, 'application.site.helper.processContactForm');
                    return [
                        'success' => false,
                        'model' => $model,
                        'message' => 'Error sending message. Please try again.'
                    ];
                }
            } else {
                Yii::log("Contact form validation failed", CLogger::LEVEL_WARNING, 'application.site.helper.processContactForm');
                return [
                    'success' => false,
                    'model' => $model
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in processContactForm: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.processContactForm');
            throw new CHttpException(500, 'Error processing contact form.');
        }
    }

    /**
     * Sends contact email
     *
     * @param ContactForm $model The contact form model
     * @return array Result array with 'success' boolean
     */
    private static function sendContactEmail($model)
    {
        try {
            $name = '=?UTF-8?B?' . base64_encode($model->name) . '?=';
            $subject = '=?UTF-8?B?' . base64_encode($model->subject) . '?=';
            $headers = "From: $name <{$model->email}>\r\n" .
                "Reply-To: {$model->email}\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/plain; charset=UTF-8";

            $result = mail(Yii::app()->params['adminEmail'], $subject, $model->body, $headers);

            return ['success' => $result];
        } catch (Exception $e) {
            Yii::log("Error sending contact email: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.sendContactEmail');
            return ['success' => false];
        }
    }

    /**
     * Handles user logout
     *
     * @return array Result with redirect URL
     */
    public static function processLogout()
    {
        try {
            Yii::log("Processing user logout", CLogger::LEVEL_INFO, 'application.site.helper.processLogout');

            Yii::app()->user->logout();

            return [
                'success' => true,
                'redirectUrl' => Yii::app()->homeUrl
            ];
        } catch (Exception $e) {
            Yii::log("Error in processLogout: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.processLogout');
            throw new CHttpException(500, 'Error processing logout.');
        }
    }

    /**
     * Performs cache operations for testing
     *
     * @return array Test results
     */
    public static function performCacheTest()
    {
        try {
            Yii::log("Performing cache test", CLogger::LEVEL_INFO, 'application.site.helper.performCacheTest');

            // Set cache value
            Yii::app()->cache->set('k1', 'karthik');

            // Delete cache value
            Yii::app()->cache->delete('k1');

            // Try to get deleted value
            $data = Yii::app()->cache->get('k1');

            // Set session value
            Yii::app()->session['userId'] = 123;

            return [
                'success' => true,
                'cacheData' => $data,
                'sessionSet' => true
            ];
        } catch (Exception $e) {
            Yii::log("Error in performCacheTest: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.helper.performCacheTest');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gets the default redirect URL for authenticated users
     *
     * @return array Redirect URL array
     */
    public static function getDefaultRedirectUrl()
    {
        return array('/inventory/dashboard/index');
    }
}
