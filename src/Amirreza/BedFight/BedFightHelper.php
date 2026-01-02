<?php

declare(strict_types=1);

namespace Amirreza\BedFight;

use Amirreza\BedFight\command\CommandLoader;
use Amirreza\BedFight\event\BedFightEventLoader;
use Amirreza\BedFight\form\custom\BedFightManageCustomForm;
use Amirreza\BedFight\form\simple\BedFightSimpleForm;
use Amirreza\BedFight\handler\BedFightHandler;
use Amirreza\BedFight\item\BedFightDefaultItem;
use Amirreza\BedFight\manager\BedFightArenaManager;
use Amirreza\BedFight\manager\BedFightGameManager;
use Amirreza\BedFight\manager\BedFightSessionManager;
use Amirreza\BedFight\manager\BedFightSetUpStepManager;
use Amirreza\BedFight\session\BedFightGameSession;
use Amirreza\BedFight\session\BedFightSetUpSession;
use Amirreza\BedFight\storage\BedFightArenaStorage;
use Amirreza\BedFight\storage\storages\SQLiteStorage;
use Amirreza\BedFight\world\WorldLoader;

class BedFightHelper
{

    private static BedFightHelper $instance;
    private BedFightSimpleForm $BFSForm;
    private BedFightManageCustomForm $BFCForm;
    private BedFightDefaultItem $IFDefault;
    private SQLiteStorage $SQLiteStorage;
    private BedFightArenaManager $BFAManager;
    private BedFightSessionManager $bedFightSessionManager;
    private BedFightSetUpSession $bedFightSetUpSession;
    private BedFightHandler $bedFightHandler;
    private BedFightSetUpStepManager $bedFightSetUpStepManager;
    private BedFightGameSession $bedFightGameSession;
    private BedFightArenaStorage $bedFightArenaStorage;
    private BedFightGameManager $bedFightGameManager;

    public static function init(): void
    {
        self::$instance = new BedFightHelper();
        $me = self::get();
        $me->vars();
        $me->classes();
    }

    private function vars(): void
    {
        $this->BFSForm = new BedFightSimpleForm();
        $this->BFCForm = new BedFightManageCustomForm();
        $this->IFDefault = new BedFightDefaultItem();
        $this->BFAManager = new BedFightArenaManager();
        $this->SQLiteStorage = new SQLiteStorage('default');
        $this->bedFightSessionManager = new BedFightSessionManager();
        $this->bedFightSetUpSession = new BedFightSetUpSession();
        $this->bedFightHandler = new BedFightHandler();
        $this->bedFightSetUpStepManager = new BedFightSetUpStepManager();
        $this->bedFightGameSession = new BedFightGameSession();
        $this->bedFightArenaStorage = new BedFightArenaStorage();
        $this->bedFightGameManager = new BedFightGameManager();
    }

    private function classes(): void
    {
        BedFightEventLoader::init();
        CommandLoader::init();
        WorldLoader::init();
        BedFightHandler::init();
    }

    public static function get(): BedFightHelper
    {
        return self::$instance;
    }

    public function BedFightSimpleForm(): BedFightSimpleForm
    {
        return $this->BFSForm;
    }

    public function BedFightManageCustomForm(): BedFightManageCustomForm
    {
        return $this->BFCForm;
    }

    public function BedFightDefaultItem(): BedFightDefaultItem
    {
        return $this->IFDefault;
    }

    public function BedFightArenaManager(): BedFightArenaManager
    {
        return $this->BFAManager;
    }

    public function SQLiteStorage(): SQLiteStorage
    {
        return $this->SQLiteStorage;
    }

    public function BedFightSessionManager(): BedFightSessionManager
    {
        return $this->bedFightSessionManager;
    }

    public function BedFightSetUpSession(): BedFightSetUpSession
    {
        return $this->bedFightSetUpSession;
    }

    public function BedFightHandler(): BedFightHandler
    {
        return $this->bedFightHandler;
    }

    public function BedFightSetUpStepManager(): BedFightSetUpStepManager
    {
        return $this->bedFightSetUpStepManager;
    }

    public function BedFightGameSession(): BedFightGameSession
    {
        return $this->bedFightGameSession;
    }

    public function BedFightArenaStorage(): BedFightArenaStorage
    {
        return $this->bedFightArenaStorage;
    }

    public function BedFightGameManager(): BedFightGameManager
    {
        return $this->bedFightGameManager;
    }
}