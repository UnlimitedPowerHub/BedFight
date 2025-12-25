<?php

declare(strict_types=1);

namespace Amirreza\BF;

use Amirreza\BF\command\CommandLoader;
use Amirreza\BF\event\EventLoader;
use Amirreza\BF\form\custom\BFCForm;
use Amirreza\BF\form\simple\BFSForm;
use Amirreza\BF\item\IFDefault;
use Amirreza\BF\manager\BFAManager;
use Amirreza\BF\session\BFSession;
use Amirreza\BF\storage\storages\SQLiteStorage;

class BFHelper {

    private static BFHelper $instance;

    private BFSForm $BFSForm;
    private BFCForm $BFCForm;

    private IFDefault $IFDefault;

    private BFSession $BFSession;


    private BFAManager $BFAManager;

    public static function init(): void {
        self::$instance = new BFHelper();
        $me = self::get();
        $me->vars();
        $me->classes();
    }

    private function vars(): void {
        $this->BFSForm = new BFSForm();
        $this->BFCForm = new BFCForm();
        $this->IFDefault = new IFDefault();
        $this->BFSession = new BFSession();
        $this->BFAManager = new BFAManager();
    }

    private function classes(): void {
        EventLoader::init();
        CommandLoader::init();
        WorldLoader::init();
    }

    public static function get(): BFHelper {
        return self::$instance;
    }

    public function BFSForm(): BFSForm {
        return $this->BFSForm;
    }

    public function BFCForm(): BFCForm {
        return $this->BFCForm;
    }

    public function IFDefault(): IFDefault {
        return $this->IFDefault;
    }

    public function BFSession(): BFSession {
        return $this->BFSession;
    }

    public function BFAManager(): BFAManager {
        return $this->BFAManager;
    }
}