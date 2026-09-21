<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

use Illuminate\Database\Eloquent\Model;

abstract class RankingModel extends Model
{
    protected $connection = 'ranking';

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:sP';
}
