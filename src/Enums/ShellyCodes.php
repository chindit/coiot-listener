<?php

namespace App\Enums;

enum ShellyCodes: int
{
    case cfgChanged = 9103;
    case output = 1101;
    case power_W = 4101;
    case energy_Wmin = 4103;
    case overpower = 6102;
    case overpowerValue_W = 6109;
    case deviceTemp_C = 3104;
    case deviceTemp_F = 3105;
    case overtemp = 6101;
    case red = 5105;
    case green = 5106;
    case blue = 5107;
    case white = 5108;
    case gain = 5102;
    case brightness = 5101;
    case colorTemp_K = 5103;
    case mode = 9101;
    case effect = 5109;
}