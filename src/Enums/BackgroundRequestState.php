<?php

namespace Jose1805\LaravelMicroservices\Enums;

enum BackgroundRequestState: string
{
    case InQueue = '0';
    case Process = '1';
}
