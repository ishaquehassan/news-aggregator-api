<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthController extends BaseController
{
    protected AuthService $authService;

    /**
     * Inject the AuthService into the controller for easier testing.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register api
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6'
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->registerUser($request->all());

        return $this->sendJsonResponse('User registered successfully.', $user);
    }

    /**
     * Login api
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->loginUser($request->get('email'), $request->get('password'));

        if (!$user) {
            return $this->sendJsonResponse('Unauthorised.', ['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->sendJsonResponse('User logged in successfully.', $user);
    }

    /**
     * Send Reset Password Link via Email api
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPasswordResetToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $token = $this->authService->getPasswordResetToken($request->get('email'));

        return $this->sendJsonResponse('Password reset token created successfully.', ['token' => $token]);
    }

    /**
     * Reset Password api
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:password_reset_tokens,token',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:6'
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $resetSuccess = $this->authService->resetPassword($request->get('email'), $request->get('password'), $request->get('token'));

        if (!$resetSuccess) {
            return $this->sendJsonResponse('Failed to reset password.', statusCode: Response::HTTP_BAD_REQUEST);
        }

        return $this->sendJsonResponse('Password has been reset.');
    }

    /**
     * Change Password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();
        $changePasswordSuccess = $this->authService->changePassword($user, $request->get('current_password'), $request->get('password'));

        if (!$changePasswordSuccess) {
            return $this->sendJsonResponse('Current password is incorrect.', statusCode: Response::HTTP_BAD_REQUEST);
        }

        return $this->sendJsonResponse('Password updated successfully.');
    }

    /**
     * Update Profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return $this->sendJsonResponse('Invalid Request.', $validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();
        $userUpdated = $this->authService->updateProfile($user, $request->all());

        if (!$userUpdated) {
            return $this->sendJsonResponse('Failed to update profile.', statusCode: Response::HTTP_BAD_REQUEST);
        }

        return $this->sendJsonResponse('Profile updated successfully.');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logoutUser($request->user());
        return $this->sendJsonResponse('Logged out successfully.');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUser(Request $request): JsonResponse
    {
        return $this->sendJsonResponse('User Profile info.', $request->user());
    }
}
