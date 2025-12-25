<?php

declare(strict_types=1);

namespace Amirreza\BF\trait;

use pocketmine\utils\TextFormat as TF;

trait CFUTrait {
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
    const BFP = TF::WHITE."Bed" . TF::RED."Fight";
    const RBFFP = self::PF.self::BFP.self::PE;
    const RBFFM = self::RBFFP.self::BWC.self::DMC;
    const RBFFW = self::RBFFP.self::BWC.self::DMCW;
}