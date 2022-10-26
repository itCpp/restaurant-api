<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmKassaInkassatsiyaUpp extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = "basesql";

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "crm_kassa_inkassatsiya_upp";
}
