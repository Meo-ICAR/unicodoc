<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\HasUserstamps;

class DocumentType extends Model
{
    use SoftDeletes, HasUserstamps;

    protected $fillable = [
        'name',
        'description',
        'code',
        'codegroup',
        'slug',
        'regex_pattern',
        'priority',
        'phase',
        'is_person',
        'is_signed',
        'is_monitored',
        'is_company',
        'is_employee',
        'is_agent',
        'is_principal',
        'is_client',
        'is_practice',
        'duration',
        'emitted_by',
        'is_sensible',
        'is_template',
        'is_stored',
        'regex',
        'is_endmonth',
        'is_AiAbstract',
        'is_AiCheck',
        'AiPattern',
    ];

    protected $casts = [
        'is_person' => 'boolean',
        'is_signed' => 'boolean',
        'is_monitored' => 'boolean',
        'is_company' => 'boolean',
        'is_employee' => 'boolean',
        'is_agent' => 'boolean',
        'is_principal' => 'boolean',
        'is_client' => 'boolean',
        'is_practice' => 'boolean',
        'is_sensible' => 'boolean',
        'is_template' => 'boolean',
        'is_stored' => 'boolean',
        'is_endmonth' => 'boolean',
        'is_AiAbstract' => 'boolean',
        'is_AiCheck' => 'boolean',
    ];
}
