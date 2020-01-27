<?php namespace Diveramkt\Uploads\Models;

use Model;

/**
 * Model
 */
class Informacoes extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'diveramkt_uploads_informacoes';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
