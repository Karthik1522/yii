<?php

class YiiAppMock
{
    private $mocked = false;
    private $app_backup = null;
    private $app_mock = null;
    private $global_mock = null;

    public function close(): void
    {
        if ($this->mocked) {
            $this->mocked = false;
            Yii::setApplication(null);
            if ($this->app_backup != null) {
                Yii::setApplication($this->app_backup);
                $this->app_backup = null;
                $this->app_mock = null;
            }
        }
    }

    public function mockParams(string $tenant_id, $params)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('hasComponent')->with('params')->andReturn(true);
        $this->app_mock->shouldReceive('getComponent')->with('params')->andReturn($params);
        $this->app_mock->shouldReceive('params')->andReturn(true);
    }

    public function mockUserForConsoleApp($return_value = true)
    {
        $this->mockApp();
        $component = Mockery::namedMock('user', stdClass::class)->makePartial();
        $this->app_mock->shouldReceive('hasComponent')->with('user')->andReturn($return_value);
        return $component;
    }

    public function mockTenant($args, $value = [], $mock_class = stdClass::class)
    {
        $this->mockApp();
        $tenant = $this->mockAppComponent('tenant', $mock_class);
        $tenant->shouldReceive('getSkuDetails')->withArgs($args)->andReturn($value);
        return $tenant;
    }

    public function mockUser(
        int $tenant_id,
        string $user_id,
        $tenant_profile = null,
        bool $isGuest = false,
        $upload_dir = '',
        $user_id_sql = null,
        $mock_class = stdClass::class,
        $user_mongo = null,
        $user_view = null,
        $tenant_sku = null,
        $get_flash = '',
    ) {
        $this->mockApp();

        $user = $this->mockAppComponent('user', $mock_class);
        $user->shouldReceive('getId')->andReturn($user_id);
        $user->shouldReceive('mongoId')->andReturn($user_id . '-mongo');
        $user->id = $user_id;
        $user->shouldReceive('getTenantId')->andReturn($tenant_id);
        $user->shouldReceive('getUserId')->andReturn($user_id);
        $user->shouldReceive('setFlash')->andReturn($tenant_id);
        $user->shouldReceive('getUserMongo')->andReturn($user_mongo);
        $user->shouldReceive('getUserMongoView')->andReturn($user_view);
        $user->shouldReceive('getTenantProfile')->andReturn($tenant_profile);
        $user->shouldReceive('setUserMongo')->andReturn(null);
        $user->shouldReceive('setUserMongoView')->andReturn(null);
        $user->shouldReceive('setTenantProfile')->andReturn(null);
        $user->shouldReceive('isSetUserMongo')->andReturn(false);
        $user->shouldReceive('isSetUserMongoView')->andReturn(false);
        $user->shouldReceive('isSetTenantProfile')->andReturn(false);
        $user->shouldReceive('upload_dir')->andReturn($upload_dir);
        $user->shouldReceive('getTenantName')->andReturn('tenant_name');
        $user->shouldReceive('setFlash')->andReturn(true);
        $user->shouldReceive('getUserId')->andReturn($user_id_sql);
        $user->shouldReceive('getTenantSku')->andReturn($tenant_sku);
        if (!empty($get_flash)) {
            $user->shouldReceive('getFlash')->andReturn($get_flash);
        }
        $user->tenant_id = $tenant_id;
        $user->isGuest = $isGuest;
        $this->app_mock->shouldReceive('getUser')->andReturn($user);

        return $user;
    }

    public function mockRequest(string $url, $isAjaxRequest = true, $pathInfo = '', $isPostRequest = false)
    {
        $this->mockApp();

        $request = $this->mockAppComponent('request');
        $request->url = $url;
        $request->baseUrl = $url;
        $this->app_mock->shouldReceive('createUrl')->andReturn($url);
        $this->app_mock->shouldReceive('createAbsoluteUrl')->andReturn($url);
        $request->shouldReceive('getPost')->andReturn($url);
        $request->isAjaxRequest = $isAjaxRequest;
        $request->pathInfo = $pathInfo;
        $request->isPostRequest = $isPostRequest;
    }

    public function mockCache($return_value = null)
    {
        $this->mockApp();

        $cache = $this->mockAppComponent('cache');
        $cache->shouldReceive('executeCommand')->andReturn($return_value);
        $cache->shouldReceive('delete')->andReturn($return_value);

        return $cache;
    }

    public function mockSession($session_data)
    {
        $this->mockApp();

        $session = $this->mockAppComponent('session', ArrayObject::class);
        $session->shouldReceive('clear');
        $session->shouldReceive('destroy');
        foreach ($session_data as $key => $value) {
            $session[$key] = $value;
        }
    }

    public function mockAssetManager()
    {
        $this->mockApp();

        $assetManager = $this->mockAppComponent('assetManager', CAssetManager::class);
        $this->app_mock->shouldReceive('getAssetManager')->andReturn($assetManager);
        $assetManager->shouldReceive('publish')->andReturn(null);
    }

    public function mockClientScript()
    {
        $this->mockApp();

        $clientScript = $this->mockAppComponent('clientScript', CClientScript::class);
        $this->app_mock->shouldReceive('getClientScript')->andReturn($clientScript);
        $this->app_mock->shouldReceive('setComponent')->andReturn(null);
        $clientScript->shouldReceive('registerScriptFile')->andReturn(null);
        $clientScript->shouldReceive('registerCssFile')->andReturn(null);
        $clientScript->shouldReceive('registerScript')->andReturn(null);
    }

    public function mockApp()
    {
        if (!$this->mocked) {
            $this->mocked = true;
            $this->app_backup = Yii::app();
            $this->app_mock = Mockery::mock(CApplication::class);

            $mongo = $this->mockAppComponent('mongodb', EMongoDB::class);
            $mongo->connectionString = 'mongodb://test';
            $mongo->dbName = 'test';

            $commonDb = $this->mockAppComponent('common', EMongoDB::class);
            $commonDb->connectionString = 'mongodb://common';
            $commonDb->dbName = 'common';

            $coreMessages = $this->mockAppComponent('coreMessages');
            $coreMessages->shouldReceive('translate')->andReturn('');

            $language = $this->mockAppComponent('language');
            $language->shouldReceive('getLanguage')->andReturn('en');
            $this->app_mock->shouldReceive('setLanguage')->andReturn('en_us');

            $auditlog = $this->mockAppComponent('auditlog', EMongoDB::class);
            $auditlog->connectionString = 'mongodb://audit';
            $auditlog->dbName = 'audit';

            $this->mockAppComponent('baseUrl');

            Yii::setApplication(null);
            Yii::setApplication($this->app_mock);

            $this->app_mock->shouldReceive('end')->andReturn(false);
        }
        return $this->app_mock;
    }

    public function mockAppComponent($component_name, $type = stdClass::class, $hasComponent = true)
    {
        $component = Mockery::namedMock($component_name, $type)->makePartial();
        $this->app_mock->shouldReceive('hasComponent')->with($component_name)->andReturn($hasComponent);
        $this->app_mock->shouldReceive('getComponent')->with($component_name)->andReturn($component);
        return $component;
    }

    public function mockAbsoluteRequest(string $url)
    {
        $this->mockApp();

        $request = $this->mockAppComponent('request');
        $request->url = $url;
        $this->app_mock->shouldReceive('createAbsoluteUrl')->andReturn($url);
    }

    public function mockController($url = null, $args = [])
    {
        $this->mockApp();
        $controller = $this->mockAppComponent('controller');
        $controller->shouldReceive('hasController')->andReturn(false);
        $controller->shouldReceive('getController')->andReturn($controller);
        $controller->shouldReceive('createUrl')->andReturn($url);
        if (!empty($args)) {
            $controller->shouldReceive('renderPartial')->andReturn(...$args);
            $controller->shouldReceive('renderPartialWithHisOwnClientScript')->andReturn(...$args);
            $controller->shouldReceive('getAction')->andReturn($controller);
            $controller->shouldReceive('getId')->andReturn(...$args);
        }

        return $controller;
    }

    public function mockRenderPartialAndInternal($output = '')
    {
        $this->mockApp();
        $controller = $this->mockAppComponent('controller');
        $controller->shouldReceive('renderInternal')->andReturn($output);
        $controller->shouldReceive('renderPartial')->andReturn($output);
    }

    public function mockSessionVariables(string $tenant_id, $session_variables)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('hasComponent')->with('session')->andReturn(true);
        $this->app_mock->shouldReceive('getComponent')->with('session')->andReturn($session_variables);
    }

    public function mockGetBaseUrl(string $url)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('baseUrl')->andReturn($url);
    }

    public function mockEnd()
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('end')->andReturn(null);
    }

    public function mockMessages()
    {
        $this->mockApp();
        $messages = $this->mockAppComponent('messages');
        $messages->shouldReceive('getMessages')->andReturn('message');
        $messages->shouldReceive('translate')->andReturn('message');
    }

    public function mockSessionComponents(string $key, $value)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('hasComponent')->with('session')->andReturn(true);
        $this->app_mock
            ->shouldReceive('getComponent')
            ->with('session')
            ->andReturn([$key => $value]);
    }

    public function mockLanguage()
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('setLanguage')->andReturn('');
        $languageHelperMock = Mockery::mock('alias:' . LanguageHelper::class);
        $languageHelperMock->shouldReceive('getTranslator')->andReturn(function ($text) {
            return $text;
        });
        $languageHelperMock->shouldReceive('getLanguage')->andReturn('en');
        $languageHelperMock->shouldReceive('getTranslatorForLanguage')->andReturn(function ($text, $array = []) {
            return $text;
        });
    }

    public function mockBaseUrl(string $url)
    {
        $this->mockApp();
        $request = $this->mockAppComponent('request');
        $request->url = $url;
        $this->app_mock->shouldReceive('getBaseUrl')->andReturn($url);
    }

    public function mockGlobal($has_component)
    {
        $this->mockApp();
        $global = $this->mockAppComponent('GlobalConsoleComponent');
        $this->app_mock->shouldReceive('hasComponent')->with('global')->andReturn($has_component);
        $this->app_mock->shouldReceive('getComponent')->with('global')->andReturn($global);
        return $global;
    }

    public function mockbasePath(string $url)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('basePath')->andReturn($url);
    }

    public function mockTheme($theme)
    {
        $this->mockApp();
        $this->app_mock->shouldReceive('hasComponent')->andReturn($theme);
        $this->app_mock->shouldReceive('getComponent')->andReturn($theme);
    }
}
