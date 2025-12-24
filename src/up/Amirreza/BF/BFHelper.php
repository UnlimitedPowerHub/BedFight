<?php

declare(strict_types=1);

namespace up\Amirreza\BF;

use up\Amirreza\BF\command\CommandLoader;
use up\Amirreza\BF\event\EventLoader;
use up\Amirreza\BF\form\custom\BFCForm;
use up\Amirreza\BF\form\simple\BFSForm;
use up\Amirreza\BF\item\IFDefault;
use up\Amirreza\BF\manager\BFAManager;
use up\Amirreza\BF\session\BFSession;
use up\Amirreza\BF\storage\storages\SQLiteStorage;
use up\Amirreza\BF\world\WorldLoader;

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