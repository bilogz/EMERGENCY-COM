package com.example.emergencycommunicationsystem.data

import com.google.gson.annotations.SerializedName

// Request for user registration
data class RegisterRequest(
    val name: String,
    val email: String,
    val phone: String,
    val password: String? = null, // Password is optional - users can sign up with Google OAuth or phone OTP
    @SerializedName("share_location") val shareLocation: Boolean = false,
    val latitude: Double? = null,
    val longitude: Double? = null,
    val address: String? = null,
    val district: String? = null,
    val barangay: String? = null,
    @SerializedName("house_number") val houseNumber: String? = null,
    val street: String? = null,
    val nationality: String? = null
)

// Request for user login
data class LoginRequest(
    val email: String? = null,
    val phone: String? = null,
    val password: String
)

// Request for Google OAuth
data class GoogleOAuthRequest(
    val action: String = "verify",
    @SerializedName("user_info") val userInfo: Map<String, Any>
)

// Request for phone OTP signup
data class PhoneOtpSignupRequest(
    val phone: String,
    val name: String? = null
)

// Request for phone OTP login
data class PhoneOtpLoginRequest(
    val phone: String,
    val name: String? = null
)

// Request for OTP verification
data class OtpVerifyRequest(
    val phone: String,
    @SerializedName("otp_code") val otpCode: String
)

// Generic response for auth operations (Register, Login)
data class AuthResponse(
    val success: Boolean,
    val message: String,
    @SerializedName("user_id") val userId: Int? = null,
    val token: String? = null,
    val username: String? = null,
    val email: String? = null,
    val phone: String? = null, // Added phone to the response
    val user: User? = null
)

// Request to get user-specific data
data class ProfileDataRequest(
    @SerializedName("user_id") val userId: Int
)

// Response containing user profile data
data class ProfileDataResponse(
    val success: Boolean,
    val message: String,
    val user: User? = null
)

// Represents a user object.
data class User(
    val name: String,
    val email: String,
    val phone: String? = null
)
