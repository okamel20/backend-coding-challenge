<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
class ApiController extends Controller
{

    public function __construct() {
    }

    // Precondition: internet is working well and github server doesn't down.
    // Postcondition: return array contain contian pure response fetched from api,
    public function repositories()
    {
        $date = now()->subDays(30)->toDateString();
        $client = new GuzzleClient;
        try {
            $res = $client->request('GET', 'https://api.github.com/search/repositories?q=created:>'.$date.'&sort=stars&order=desc&per_page=100');
            if ($res->getStatusCode() == 200) {
                 $response_list = json_decode($res->getBody(), true);
                 return response()->json(['success' => 'success','response_list' => $response_list], 200);
            } else {
                return response()->json(['error' => 'invalid'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e], 401);
        }
    }

    // Precondition: internet is working well and github server doesn't down.
    // Postcondition: return array contain contian pure response fetched from api,
    // and array have all language and their repos. 
    public function languages()
    {
        $date = now()->subDays(30)->toDateString();
        $client = new GuzzleClient;
        try {
            $res = $client->request('GET', 'https://api.github.com/search/repositories?q=created:>'.$date.'&sort=stars&order=desc&per_page=100');
            if ($res->getStatusCode() == 200) {
                 $response_list = json_decode($res->getBody(), true);
                 $customized_list =  $this->format_response($response_list['items']);
                 $list_without_null_language =  $this->filtering(null, $customized_list);
                 $unique_list =  $this->set_of_language($list_without_null_language);
                 $language_list =  $this->list_of_repos($list_without_null_language, $unique_list);
                 return response()->json(['success' => 'success','language_list' => $language_list], 200);
            } else {
                return response()->json(['error' => 'invalid'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e], 401);
        }
    }

    // Precondition: list element should be an array.
    // Postcondition: return array contian needed objects formation. 
    public function format_response($list)  {
        $new_list = [];
        foreach ($list as $key => $value) {
            $new_list[$key]['id'] = $value['id'];
            $new_list[$key]['name'] = $value['name'];
            $new_list[$key]['description'] = $value['description'];
            $new_list[$key]['url'] = $value['url'];
            $new_list[$key]['language'] = $value['language'];
        }
        return $new_list;
    }

    // Precondition: target can be null or array,
    // and list should be an arrays and not null,
    // and doesn't have null elements in language key.
    // Postcondition: return cleaned array of nulls,
    // or return needed repos for filteration.   
    public function filtering($target, $list)
    {
        $collection = collect($list);
        if ($target == null) {
            return  $collection->where('language','!=', null)->values();
        } else {
            return  $collection->where('language', $target)->values();
        }
    }

    // Precondition: list should be an array,
    // and contain duplication element to use it.
    // Postcondition: return new array have unique values.
    public function set_of_language($list)
    {
        $new_list = [];
        foreach ($list as $key => $value) {
            $new_list[$key] = $value['language'];
        }
        return $new_list;
    }

    // Precondition: list and target not have a null elementm,
    // and both are arrays and target should have unique element.
    // Postcondition: return new array with properties for every language,
    // such as language name and number of repos and repos use this language. 
    public function list_of_repos($list, $target) {
        $new_list = [];
        for ($i = 0; $i < count($target); $i++) {
            $language_list = $this->filtering($target[$i], $list);
            $new_list[$i]['name'] = $target[$i];
            $new_list[$i]['count'] = count($language_list);
            $new_list[$i]['repo_list'] = $language_list;
        }
        return $new_list;
    }

}