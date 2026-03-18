class Queue extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id','instrument_id','user_name','email','queue_position'
    ];
}