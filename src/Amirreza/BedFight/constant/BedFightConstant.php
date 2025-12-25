<?php

declare(strict_types=1);

namespace Amirreza\BedFight\constant;

use pocketmine\utils\TextFormat as TF;

class BedFightConstant {
    const PF = TF::DARK_AQUA . "[";
    const PE = TF::DARK_AQUA . "]";
    const PLUL = TF::DARK_AQUA . "◸";
    const PLUR = TF::DARK_AQUA . "◹";
    const PLDL = TF::DARK_AQUA . "◺";
    const PLDR = TF::DARK_AQUA . "◿";
    const BWC = TF::GRAY . " - ";
    const DMC = TF::AQUA;
    const DMCW = TF::YELLOW;
    const NL = "\n";
    const L = "––––––––––––––––––––––––––––––";
    const NLWLU = self::NL.self::PLUL.self::L.self::PLUR.self::NL;
    const NLWLD = self::NL.self::PLDL.self::L.self::PLDR.self::NL;
    const BedFightPrefix = TF::WHITE."Bed" . TF::RED."Fight";
    const RBFFP = self::PF.self::BedFightPrefix.self::PE;
    const RBFFM = self::RBFFP.self::BWC.self::DMC;
    const RBFFW = self::RBFFP.self::BWC.self::DMCW;
}