<?php
namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;


class ClientController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|unique:clients,phone|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $client = Client::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::guard('client')->login($client);

        return $this->respondWithToken($token, Auth::guard('client')->user());

        }

    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required',
            'password' => 'required|min:6',
        ]);

        $credentials = $request->only('phone', 'password');

        if (! $token = Auth::guard('client')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, Auth::guard('client')->user());
    }

    public function logout()
    {
        Auth::guard('client')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('client')->refresh());
    }

    public function me()
    {
        return response()->json(Auth::guard('client')->user());
    }

    protected function respondWithToken($token, $user = null)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => $user,
            'guard'        => 'client',
        ]);
    }

    public function index()
    {
        $clients = Client::latest()->paginate(15);
        return $this->successResponse($clients, 'Clients retrieved successfully');
    }
    public function update(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'phone'    => 'sometimes|string|unique:clients,phone,' . $client->id . '|max:20',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->name;
        }

        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $client->update($data);

        return $this->updatedResponse($client, 'Client updated successfully');
    }

    /**
     * Remove the specified client.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return $this->deletedResponse('Client deleted successfully');
    }
}
