<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Menggambarkan tiap kategori penerima
 *
 * id            primary key
 * name          string
 * pic_name      string
 * description   string
 * district_code string
 * total_stock   integer
 * total_used    integer
 * timestamps
 */
class Recipient extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'pic_name',
    'description',
    'district_code',
    'location_province_code',
    'total_stock',
    'total_used',
  ];

  /**
  * The model's default values for attributes.
  *
  * @var array
  */
  protected $attributes = [
  ];

  // ======================= RELATIONSHIPS ============================
  /**
   * Get transactions related to this recipient
   */
  public function transactions()
  {
      return $this->hasMany('App\Transaction', 'id_recipient');
  } 
}
