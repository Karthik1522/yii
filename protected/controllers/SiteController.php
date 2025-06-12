<?php

// Import the helper class
Yii::import('application.components.helpers.SiteHelper');

class SiteController extends Controller
{
    /**
     * Declares class-based actions.
     */
    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
            ),
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }

    public function accessRules()
    {
        return array(
            array('allow',
                'actions' => array('login', 'error', 'captcha', 'page', 'register'),
                'users' => array('*'),
            ),
            array('allow',
                'actions' => array('index', 'logout'),
                'users' => array('@'),
            ),
            // No specific role checks needed for SiteController's basic actions yet.
            // If you had an admin-only page in SiteController, you'd add:
            // array('allow',
            //     'actions' => array('adminOnlyPage'),
            //     'expression' => 'Yii::app()->user->isAdmin()', // Use our WebUser method
            // ),
            array('deny',  // Deny all other actions for all users
                'users' => array('*'),
            ),
        );
    }

    public function actionTest()
    {
        try {
            Yii::log('Starting actionTest', CLogger::LEVEL_INFO, 'application.site.test');

            $testResults = SiteHelper::performCacheTest();

            echo "Inside ActionTest<br>";
            echo "Cache data: " . ($testResults['cacheData'] ?: 'null') . "<br>";
            echo "Session set: " . ($testResults['sessionSet'] ? 'Yes' : 'No') . "<br>";

            Yii::log('actionTest completed successfully', CLogger::LEVEL_INFO, 'application.site.test');
        } catch (Exception $e) {
            Yii::log('Error in actionTest: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.test');
            throw $e;
        }
    }

    public function actionIndex()
    {
        try {
            Yii::log('Starting actionIndex', CLogger::LEVEL_INFO, 'application.site.index');

            $redirectUrl = SiteHelper::getDefaultRedirectUrl();
            $this->redirect($redirectUrl);

            Yii::log('actionIndex completed successfully', CLogger::LEVEL_INFO, 'application.site.index');
        } catch (Exception $e) {
            Yii::log('Error in actionIndex: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.index');
            throw $e;
        }
    }

    public function actionError()
    {
        try {
            Yii::log('Starting actionError', CLogger::LEVEL_INFO, 'application.site.error');

            if ($error = Yii::app()->errorHandler->error) {
                if (Yii::app()->request->isAjaxRequest) {
                    echo $error['message'];
                } else {
                    $this->render('error', $error);
                }
            }

            Yii::log('actionError completed successfully', CLogger::LEVEL_INFO, 'application.site.error');
        } catch (Exception $e) {
            Yii::log('Error in actionError: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.error');
            throw $e;
        }
    }

    /**
     * Displays the contact page
     */
    public function actionContact()
    {
        try {
            Yii::log('Starting actionContact', CLogger::LEVEL_INFO, 'application.site.contact');

            $model = new ContactForm();
            if (isset($_POST['ContactForm'])) {
                $result = SiteHelper::processContactForm($_POST['ContactForm']);
                $model = $result['model'];

                if ($result['success']) {
                    Yii::app()->user->setFlash('contact', $result['message']);
                    $this->refresh();
                } elseif (isset($result['message'])) {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $this->render('contact', array('model' => $model));

            Yii::log('actionContact completed successfully', CLogger::LEVEL_INFO, 'application.site.contact');
        } catch (Exception $e) {
            Yii::log('Error in actionContact: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.contact');
            throw $e;
        }
    }

    /**
     * Displays the login page
     */
    public function actionLogin()
    {
        try {
            Yii::log('Starting actionLogin', CLogger::LEVEL_INFO, 'application.site.login');

            $model = new LoginForm();

            if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
                echo CActiveForm::validate($model);
                Yii::app()->end();
            }

            if (isset($_POST['LoginForm'])) {
                $result = SiteHelper::processLogin($_POST['LoginForm']);
                $model = $result['model'];

                if ($result['success']) {
                    $this->redirect($result['redirectUrl']);
                }
            }

            $this->render('login', array('model' => $model));

            Yii::log('actionLogin completed successfully', CLogger::LEVEL_INFO, 'application.site.login');
        } catch (Exception $e) {
            Yii::log('Error in actionLogin: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.login');
            throw $e;
        }
    }

    public function actionRegister()
    {
        try {
            Yii::log('Starting actionRegister', CLogger::LEVEL_INFO, 'application.site.register');

            $model = new User('register');

            if (isset($_POST['User'])) {
                $result = SiteHelper::processRegistration($_POST['User']);
                $model = $result['model'];

                if ($result['success']) {
                    Yii::app()->user->setFlash('success', $result['message']);
                    $this->redirect($result['redirectUrl']);
                } else {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $this->render('register', ['model' => $model]);

            Yii::log('actionRegister completed successfully', CLogger::LEVEL_INFO, 'application.site.register');
        } catch (Exception $e) {
            Yii::log('Error in actionRegister: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.register');
            throw $e;
        }
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        try {
            Yii::log('Starting actionLogout', CLogger::LEVEL_INFO, 'application.site.logout');

            $result = SiteHelper::processLogout();
            $this->redirect($result['redirectUrl']);

            Yii::log('actionLogout completed successfully', CLogger::LEVEL_INFO, 'application.site.logout');
        } catch (Exception $e) {
            Yii::log('Error in actionLogout: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.site.logout');
            throw $e;
        }
    }
}
