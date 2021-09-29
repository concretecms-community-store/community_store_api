<?php
namespace Concrete\Package\CommunityStoreApi;

use Concrete\Core\Package\Package;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_api';
    protected $appVersionRequired = '8.4';
    protected $pkgVersion = '1.0.1';
    protected $packageDependencies = ['community_store'=>'2.4'];

    protected $pkgAutoloaderRegistries = [
        'src/CommunityStoreApi' => '\Concrete\Package\CommunityStoreApi',
    ];

    public function getPackageDescription()
    {
        return t("Community Store API");
    }

    public function getPackageName()
    {
        return t("Community Store API");
    }

    public function install()
    {
        parent::install();
        $this->addScopes();
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->addScopes();
    }

    public function uninstall()
    {
        parent::uninstall();
        $this->removeScopes();
    }

    public function on_start() {

        $config = $this->app->make("config");
        if ($this->app->isInstalled() && $config->get('concrete.api.enabled')) {
            $router = $this->app->make('router');
            $list = new RouteList();
            $list->loadRoutes($router);
        }
    }

    private function addScope($scope, $description)
    {
        $db = $this->app->make('database')->connection();

        $existingScope = $db->fetchColumn('select identifier from OAuth2Scope where identifier = ?', [
            $scope
        ]);
        if (!$existingScope) {
            $db->insert('OAuth2Scope', ['identifier' => $scope, 'description' => $description]);
        }
    }

    private function removeScope($scope)
    {
        $db = $this->app->make('database')->connection();

        $db->execute('delete from OAuth2Scope where identifier = ?', [
            $scope
        ]);
    }

    private function addScopes()
    {
        $this->addScope('cs:products:read', t('Read Community Store product information'));
        $this->addScope('cs:products:write', t('Write Community Store product information'));
        $this->addScope('cs:orders:read', t('Read Community Store order information'));
        $this->addScope('cs:orders:write',t('Write Community Store order information'));
    }

    private function removeScopes()
    {
        $this->removeScope('cs:products:read');
        $this->removeScope('cs:products:write');
        $this->removeScope('cs:orders:read');
        $this->removeScope('cs:orders:write');
    }
}
