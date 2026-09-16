<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Profile
 */
class ProfileController extends Controller
{
    protected Request $request;
    protected array $posted;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
    }

    /**
     * Get Profile
     *
     * Get detail of data profile.
     * @group Profile
     * Below are the endpoints for get currently user logged in profile data and update profile.
     * @authenticated
     * @response 200 {"code": 200,"message": "success","data": {"id": 1,"name": "administrator","employee_id_number": "0000000000000","employee_registration_number": "0000000000000","username": "admin","email": "admin@setwapres.go.id","role_name": "administrator"}}
     */
    public function show()
    {
        $user = $this->request->user();

        $role = DB::table('roles');
        $role->where('id', $user->role_id);
        $role->select('name');
        $role = $role->first();

        $photoProfile = $this->getDocument($user->photo_profile, true);

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'registration_number' => $user->registration_number,
            'photo_profile' => $photoProfile,
            'username' => $user->username,
            'email' => $user->email,
            'role_name' => $role->name,
        ];
        return $this->response(200, 'success', $data);
    }

    /**
     * Update Profile
     *
     * Update currently user logged in profile data. <br/><br/>
     * <strong>Note:</strong> still bugs on elements theme when content-type is multiple/form-data <br/>
     * issue at https://github.com/knuckleswtf/scribe/issues/831
     * @group Profile
     * @authenticated
     * @response 200 {"code": 200,"message": "Profil berhasil diupdate.","data": null}
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            DB::beginTransaction();

            DB::table('users')->where('id', $this->request->user()->id)->updateTs([
                'username' => $this->request->username,
                'email' => $this->request->email,
            ]);

            // Check if file submitted
            if ($this->request->hasFile('photo_profile')) {
                $path = $this->uploadDocument($this->request->file('photo_profile'), 'photo_profile');
                DB::table('users')->where('id', $this->request->user()->id)->updateTs([
                    'photo_profile' => $path,
                ]);
            }

            // Check if password submitted
            if (isset($this->request->password) && $this->request->password !== null) {
                if (!Hash::check($this->request->old_password, $this->request->user()->password)) {
                    return $this->response(400, 'Password saat ini tidak sesuai.');
                }

                DB::table('users')->where('id', $this->request->user()->id)->updateTs([
                    'password' => Hash::make($this->request->password),
                ]);
            }
            DB::commit();
            return $this->response(200, 'Profil berhasil diupdate.');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::warning($th);
            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
