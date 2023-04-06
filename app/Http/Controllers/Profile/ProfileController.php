<?php

namespace App\Http\Controllers\Profile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ProfileRelated\Profile;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => []]);
    }
    //http://127.0.0.1:8000/api/profile/3
    public function show(int $uid) 
    {
        // $profile = Profile::where('user_id', $uid)->first();
        $profile = Profile::firstOrCreate(['user_id' => $uid]);

        if (!$profile) {
            // If the profile is not found, load the user
            $user = User::find($uid);

            if (!$user) {
                // If the user is not found, return an error response
                return response()->json(['error' => 'User not found'], 404);
            }

            // Create an array containing the user's basic information
            $defaultProfileData = [
                'user_id' => $user->id,
                'surname' => ucwords($user->surname),
                'name' => ucwords($user->name),
                'username' => $user->username,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'avatar' => asset('images/avatar.jpg'), // Default avatar
            ];

            // Return the user's basic information as a JSON object
            return response()->json($defaultProfileData);
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $profile = Profile::where('user_id', $user->id)->first();

        $profile->fill($request->all());
        $profile->save();
        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function storeNewprofession(Request $request)
    {
        $user = Auth::user();
        // $profile = Profile::where('user_id', $user->id)->first();
        // ['created_by_id'=>$user->id, 'category_id' => $request->category_id, 
        // 'name' => $request->category_id],
        
        try{
            \App\Models\ProfileRelated\AllProfession::create($request->all()+ ['created_by_id' => $user->id]);
            }catch(\Illuminate\Database\QueryException $e){
                return ['error'=>$e->getMessage()];
            }
        return response()->json(\App\Models\ProfileRelated\AllProfession::where('name',$request->name)->first());
        // return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function getMarried($action) {
        
            $mappedArray[] = ['label' => 'Married','value' => true,];
            $mappedArray[]=['label' => 'Single','value' => false,];
       
        return response()->json($mappedArray);
    }
    function convertArray2LabelValues($contents){
        foreach ($contents as $value) {
            $mappedArray[] = [
                'label' => $value->name,
                'value' => $value->id,
            ];
        } 
        return $mappedArray;
    }
    public function getGender ($action) {
        $user = Auth::user();
        $profile = $user->profile;
        // return $profile->getStatusEnumValues($action);
        $contents = $profile->getStatusEnumValues($action);
        // $mappedArray = [];
        foreach ($contents as $value) {
            $mappedArray[] = [
                'label' => $value,
                'value' => $value,
            ];
        } 
        // return $mappedArray;

           
        return response()->json( $mappedArray);
    }
    public function getEducation ($action) {
        $contents = \App\Models\ProfileRelated\Qualification::select('id','name')->get();
        // $mappedArray = [];
        // foreach ($contents as $value) {
        //     $mappedArray[] = [
        //         'label' => $value->name,
        //         'value' => $value->id,
        //     ];
        // }    
        return response()->json($this->convertArray2LabelValues($contents));
    }

    public function getProfession_category ($action) {
        $contents = \App\Models\ProfileRelated\ProfessionCategory::select('id','name')->get();
    
        return response()->json($this->convertArray2LabelValues($contents));
    }
    public function getProfession (Request $request) {
        // return $request;
        $contents = \App\Models\ProfileRelated\AllProfession::where("category_id",$request->category_id)->select('id','name')->get();
        // return $contents;
        return response()->json($this->convertArray2LabelValues($contents));
    }




    public function handleAction($action) {
        
        $methodName = 'get' . ucfirst($action);
    
        if (method_exists($this, $methodName)) {
            // if ($action=='profession') {return $this->{$methodName}(Request $action);}
            // else {
                return $this->{$methodName}($action);
            // }
            
        } else {
            abort(404);
        }
    }


//     public function edit()
// {
//     $user = Auth::user();
//     $profile = $user->profile;

//     return  compact('profile');
// }
    // public function saveCoverPhoto(Request $request, $id)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

    //     if (!$request->hasFile('cover_photo')) {
    //         return response()->json(['error' => 'No cover photo provided'], 400);
    //     }

    //     $coverPhoto = $request->file('cover_photo');
    //     $filename = time() . '_' . $coverPhoto->getClientOriginalName();
    //     $path = $coverPhoto->storeAs('public/cover_photos', $filename);

    //     $profile = $user->profile ?? new Profile(['user_id' => $id]);
    //     $profile->cover_photo = $filename;
    //     $profile->save();

    //     return response()->json(['message' => 'Cover photo saved successfully'], 200);
    // }

    public function upload(Request $request,$field)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(public_path('images'), $imageName);

        // Store the image path in the profiles table
        $user = Auth::user();
        $profile = $user->profile;



        $profile->{$field} = 'images/' . $imageName;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded and saved successfully',
            'file_path' => 'images/' . $imageName,
        ]);
    }

    public function getCoverPhoto($id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            // 'cover_photo_uri' => url('/') . '/' . $profile->cover_photo,
            'cover_photo_uri' => asset($profile->cover_photo)
        ]);
    }




    public function updateField(Request $request, $field)
    {
        // return $request;
        $user = $request->user();
        $profile = $user->profile;
        $altfield=$field;
        if ($field=='username'){
            $user->{$field} = $request->input('value');
            $user->save();
            return response()->json( [$field =>$user->{$field} ]);
        }
        
        
        switch ($field) {
            case 'native_place':
                $field='native_place_id';
                // $profile->native_place_id = $request->input('value');
                break;
            case 'work_place':

                $field='work_place_id';
                
                // $profile->work_place_id = $request->input('value');
                break;
            case 'education':
                $field='education_id';
                // $profile->education_id = $request->input('value');
                break;
            case 'profession':
                $field='profession_id';
                // $profile->profession_id = $request->input('value');
       
            default:
                $field=$field;
                break;
                // $profile->{$field} = $request->input('value');
                // return response()->json(['error' => 'value'], 400);
        }
        $profile->{$field} = $request->input('value');

        $profile->save();
        
        switch ($altfield) {
            case 'native_place':
                $altvalue=$profile->getNativePlace();
                
                break;
            case 'work_place':

                $altvalue=$profile->getWorkPlace();
                
                
                break;
            case 'education':
                $altvalue=$profile->education->name;
                
                break;
            case 'profession':
                $altvalue=$profile->profession->name;
            default:
                $altvalue=$profile->{$field};
                break;
            }
            // [$field => $profile->{$field}],
        return response()->json([$altfield =>$altvalue ]);
    }
}


