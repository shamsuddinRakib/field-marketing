<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HardDeletes;

class FirewallRule extends Model
{
    use HardDeletes;

    protected $table = 'firewall_rules';
    protected $fillable = ['ip_address','type','comments'];

    // small helper
    public function isBlocked(): bool { return $this->type === 'block'; }
    public function isAllowed(): bool { return $this->type === 'allow'; }
}
