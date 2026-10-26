<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalRolePermission extends Model
{
    protected $table = 'customer_portal_role_permissions';

    public $timestamps = false;

    protected $fillable = ['role_id', 'module', 'action'];

    public function role()
    {
        return $this->belongsTo(CustomerPortalRole::class, 'role_id');
    }
}
