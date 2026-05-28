<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

<<<<<<< HEAD
class Customer extends Model
{
    use HasFactory;
=======

class Customer extends Model
{
    use HasFactory;

>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'document_type',
<<<<<<< HEAD
        'document_number',
        'birth_date',
        'status',
        'source',
        'company',
    ];
=======
        'status',
        'source',
        'company',
        'notes'
    ];

    public function customer_addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
}
