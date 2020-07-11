<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MichaelDrennen\Geonames\Models\Geoname;

class GeonamesController extends Controller
{

    /**
     * This class does a lot of querying on the geonames table. Most of the time we're going to want the same set of
     * fields. No need to duplicate the code all over.
     * @var array
     */
    protected $defaultGeonamesFields = [
        'asciiname','latitude','longitude',
        'country_code',
        'admin1_code', 'feature_code',
        'admin2_code', ];

    /**
     * @param string|null $term
     * @param string $type
     * @param string|null $country
     * @return Builder[]|Collection|null
     */
    public function GetGeonames(string $term = null, string $type='country', string $country=null)
    {
        $query = Geoname::on( env( 'DB_GEONAMES_CONNECTION' ) )
            ->select( $this->defaultGeonamesFields )
            ->where( 'asciiname', 'LIKE',  '%'.$term.'%' );

        if ($country){
           $query = $query->where('country_code','=', $country);
        }

        $feature_code = null;
        $feature_class = 'A';

        switch ($type){
            case 'region':
                $feature_code = 'ADM1';
                break;

            case 'country':
                $feature_code = 'PCLI';
                break;

            case 'city':
                $feature_code = 'ADM3';
                break;
            default :
                return null;
                break;
        }

        if ($feature_code){
            $query->where('feature_code','=', $feature_code);
        }

        $query->orderBy( 'country_code' )->take(10);

        return  $query->get();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function SearchCities(Request $request){
        $term = $request->input('q');
        $country = $request->input('country');

        $results = $this->GetGeonames($term, 'city', $country);

        return response()->json( $results );
    }

    public function SearchCountries(Request $request){

        $term = $request->input('q');

        $results = $this->GetGeonames($term, 'country');

        return response()->json( $results );
    }
    public function SearchRegions(Request $request){

        $term = $request->input('q');
        $country = $request->input('country');

        $results = $this->GetGeonames($term, 'region', $country);

        return response()->json( $results );
    }

    public function SearchAll(Request $request){
        $term = $request->input('q');

        $query = Geoname::on( env( 'DB_GEONAMES_CONNECTION' ) )
            ->select( $this->defaultGeonamesFields )
            ->where( 'asciiname', 'LIKE', '%'.$term. '%' )
            ->where('country_code','SV')
            ->where('feature_code', 'ADM2')->get();

        return response()->json( $query );
    }
}
