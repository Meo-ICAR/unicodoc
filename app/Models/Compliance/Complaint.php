<?php
namespace App\Models\Compliance;

use App\Models\RequestRegistry;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    // Specifichiamo la connessione corretta
    protected $connection = 'mysql_compliance';

    // Nome della tabella nel DB UnicoCompliance
    protected $table = 'complaint_registry';

    protected $fillable = [
        'request_registry_id',  // ID della pratica in UnicoDoc
        'company_id',  // ID dell'azienda nel BPM
        'complaint_number',
        'status',
        'category',
        // ... altri campi
    ];

    /**
     * Relazione inversa: Dal Reclamo (Compliance DB) alla Pratica (Doc DB)
     */
    public function parentRequest()
    {
        return $this->belongsTo(RequestRegistry::class, 'request_registry_id');
    }
}
