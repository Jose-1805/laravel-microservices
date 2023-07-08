<?php

namespace Jose1805\LaravelMicroservices\Enums;

enum BackgroundRequestState
{
    case InQueue = '0';
    case Process = '1';
}
