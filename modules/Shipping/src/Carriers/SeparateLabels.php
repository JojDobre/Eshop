<?php

namespace Modules\Shipping\Carriers;

/**
 * Kuriér vie vrátiť štítok iba pre jednu zásielku (PDF sa nedajú spojiť).
 * Hromadná tlač potom pribalí štítok každej zásielky ako samostatný súbor do ZIP.
 */
interface SeparateLabels {}
