package com.example.emergencycommunicationsystem.network

import com.example.emergencycommunicationsystem.data.AuthResponse
import com.example.emergencycommunicationsystem.data.LogoutRequest
import com.example.emergencycommunicationsystem.data.ProfileDataRequest
import com.example.emergencycommunicationsystem.data.ProfileDataResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

interface AuthApiService {
    @POST("register.php")
    suspend fun registerUser(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("login.php")
    suspend fun loginUser(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("google-oauth-mobile.php")
    suspend fun googleOAuth(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("send-otp.php")
    suspend fun sendOtp(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("verify-otp.php")
    suspend fun verifyOtp(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("register-after-otp.php")
    suspend fun registerAfterOtp(@Body body: Map<String, @JvmSuppressWildcards Any>): Response<AuthResponse>

    @POST("profile_data.php")
    suspend fun getProfileData(@Body request: ProfileDataRequest): ProfileDataResponse

    @POST("logout.php")
    suspend fun logout(@Body request: LogoutRequest): Response<AuthResponse>
}