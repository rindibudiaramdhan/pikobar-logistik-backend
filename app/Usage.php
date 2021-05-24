<?php

/**
 * Class for storing all method & data regarding item usage information, which
 * are retrieved from Pelaporan API
 */

namespace App;

use GuzzleHttp;
use JWTAuth;
use DB;
use App\PoslogProduct;
use App\SohLocation;

class Usage
{
    static $client = null;

    static function getClient()
    {
        if (static::$client == null) {
            static::$client = new GuzzleHttp\Client();
        }

        return static::$client;
    }

    /**
     * Request authorization token from pelaporan API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanAuthToken()
    {
        // login first
        $login_url = config('pelaporan.url') . '/api/login';
        $res = static::getClient()->post($login_url, [
            'json'   => [
                'username' => config('pelaporan.username'),
                'password' => config('pelaporan.password'),
            ],
            'verify' => false,
        ]);
        if ($res->getStatusCode() != 200) {
            return [ response()->format(500, 'Internal server error'), null ];
        }
        return json_decode($res->getBody())->data->token;
    }

    /**
     * Request used rdt stock data from pelaporan dinkes API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanCitySummary()
    {
        // retrieving summary by cities endpont
        $token = static::getPelaporanAuthToken();
        $url = config('pelaporan.url') . '/api/rdt/summary-by-cities';
        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        return self::returnData($res);
    }

    /**
     * Request used Pelaporan Integrate
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanData($url)
    {
        $token = static::getPelaporanAuthToken();
        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        return self::returnData($res);
    }

    static function returnData($res)
    {
        if ($res->getStatusCode() != 200) {
            error_log("Error: pelaporan API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            // Extract the data
            return [ null, json_decode($res->getBody())->data ];
        }
    }

    /**
     * Request used rdt stock data (grouped by faskes, filter only from the same
     * kob/kota) from pelaporan dinkes API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanFaskesSummary()
    {
        // retrieving summary by cities endpont
        $token = static::getPelaporanAuthToken();
        $district_code = JWTAuth::user()->code_district_city;
        $url  = config('pelaporan.url') . '/api/rdt/faskes-summary-by-cities';
        $url .= "?district_code=$district_code";

        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: pelaporan API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            // Extract the data
            $raw_list = json_decode($res->getBody())->data;

            // format data
            $faskes_list = [];
            foreach ($raw_list as $row) {
                $faskes_list[] = [
                    "faskes_name" => $row->_id,
                    "total_stock" => null,
                    "total_used" => $row->total,
                ];
            }

            return [ null,  $faskes_list ];
        }
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getLogisticStock($param, $api, $baseApi)
    {
        $apiKey = PoslogProduct::isDashboardAPI($baseApi) ? config('dashboardexecutive.key') : config('wmsjabar.key');
        $apiLink = PoslogProduct::isDashboardAPI($baseApi) ? config('dashboardexecutive.url') : config('wmsjabar.url');
        $apiFunction = $api ? $api : '/api/soh_fmaterialgroup';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
            'body' => $param
        ]);
        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return PoslogProduct::isDashboardAPI($baseApi) ? json_decode($res->getBody())->data : json_decode($res->getBody())->msg;
        }
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getLogisticStockByLocation($id)
    {
        $param = '{"soh_location":"' . $id . '"}';
        $apiKey = config('wmsjabar.key');
        $apiLink = config('wmsjabar.url');
        $apiFunction = '/api/soh_flocation';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
            'body' => $param
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return json_decode($res->getBody())->msg;
        }
    }

    static function getPoslogItem($fieldPoslog, $valuePoslog, $materialName)
    {
        $poslogProduct = [];
        try {
            $poslogProduct = PoslogProduct::select(DB::raw('CONCAT("(", material_id, ") ", material_name) as name'), 'material_id', 'material_name', 'soh_location', 'soh_location_name', 'uom', 'matg_id', 'stock_ok', 'stock_nok')
            ->where(function ($query) use ($fieldPoslog, $valuePoslog, $materialName) {
                $query->where('stock_ok', '>', 0);
                if ($valuePoslog) {
                    $query->where($fieldPoslog, '=', $valuePoslog);
                }

                if ($materialName) {
                    $query->where(DB::raw('CONCAT("(", material_id, ") ", material_name)'), 'LIKE', '%' . $materialName . '%');
                }
            })->orderBy('material_name','asc')->orderBy('soh_location','asc')->orderBy('stock_ok','desc')->get();
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }
        return $poslogProduct;
    }

    static function getPoslogItemUnit($fieldPoslog, $valuePoslog, $materialName)
    {
        $data = self::getPoslogItem($fieldPoslog, $valuePoslog, $materialName);
        $dataFinal = [];
        foreach ($data as $val) {
            $dataFinal[] = [
                'id' => $val->uom,
                'name' => $val->uom
            ];
        }
        if (!$dataFinal) {
            $dataFinal[] = [
                'id' => 'PCS',
                'name' => 'PCS'
            ];
        }
        return $dataFinal;
    }

    static function syncDashboard()
    {
        $data = [];
        $api = '/master/inbound_detail?search=gsheet';
        $param = '';
        $baseApi = PoslogProduct::API_DASHBOARD;
        $materials = static::getLogisticStock($param, $api, $baseApi);
        $data = static::setPoslogProduct($materials, $baseApi, $data);
        PoslogProduct::updatingPoslogProduct($data, $baseApi);
    }

    static function syncWmsJabar()
    {
        $data = [];
        $sohLocation = SohLocation::all();
        $baseApi = PoslogProduct::API_POSLOG;
        foreach ($sohLocation as $val) {
            $materials = static::getLogisticStockByLocation($val['location_id']);
            $data = static::setPoslogProduct($materials, $baseApi, $data);
        }
        PoslogProduct::updatingPoslogProduct($data, $baseApi);
    }

    static function setPoslogProduct($materials, $baseApi, $data)
    {
        foreach ($materials as $material) {
            $key = static::getKeyIndex($material);
            if (!isset($data[$key])) {
                $data = PoslogProduct::setValue($data, $material, $baseApi);
            } else {
                $data = PoslogProduct::addStock($data, $material);
            }
        }
        return $data;
    }

    static function getLocationId($material)
    {
        return isset($material->soh_location) ? $material->soh_location : ($material->inbound[0]->inbound_location ? $material->inbound[0]->inbound_location : $material->inbound[0]->whs_name);
    }

    static function getKeyIndex($material)
    {
        return $material->material_id .'-'. static::getLocationId($material);
    }

    static function getStockOk($material)
    {
        return isset($material->stock_ok) ? ($material->stock_ok - $material->booked_stock) : $material->qty_good_in;
    }

    static function getStockNok($material)
    {
        return isset($material->stock_nok) ? $material->stock_nok : $material->qty_notgood_in;
    }

    static function getSohLocationName($material)
    {
        return isset($material->soh_location_name) ? $material->soh_location_name : $material->inbound[0]->whs_name;
    }

    static function getUnitofMaterial($material)
    {
        return isset($material->UoM) ? $material->UoM : $material->uom;
    }
}
