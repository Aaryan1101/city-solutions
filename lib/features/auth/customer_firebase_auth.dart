import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';

import '../mart/domain/mart_models.dart';

class FirebaseAuthAvailability {
  const FirebaseAuthAvailability({
    required this.enabled,
    required this.phoneEnabled,
    required this.googleEnabled,
  });

  final bool enabled;
  final bool phoneEnabled;
  final bool googleEnabled;

  static const disabled = FirebaseAuthAvailability(
    enabled: false,
    phoneEnabled: false,
    googleEnabled: false,
  );
}

class CustomerFirebaseAuth {
  CustomerFirebaseAuth._();

  static final CustomerFirebaseAuth instance = CustomerFirebaseAuth._();
  static const _apiRoot = String.fromEnvironment(
    'CITY_API_ROOT',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1',
  );

  final _http = HttpClient()..connectionTimeout = const Duration(seconds: 8);
  bool _googleInitialized = false;

  Future<FirebaseAuthAvailability> availability() async {
    try {
      final request = await _http.getUrl(Uri.parse('$_apiRoot/auth/config'));
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      final response = await request.close();
      final decoded = await _decode(response);
      final data = decoded['data'] is Map
          ? Map<String, dynamic>.from(decoded['data'] as Map)
          : const <String, dynamic>{};
      return FirebaseAuthAvailability(
        enabled: data['enabled'] == true,
        phoneEnabled: data['phone_enabled'] == true,
        googleEnabled: data['google_enabled'] == true,
      );
    } catch (_) {
      return FirebaseAuthAvailability.disabled;
    }
  }

  Future<MartCustomerSession> signInWithGoogle() async {
    if (!_googleInitialized) {
      await GoogleSignIn.instance.initialize();
      _googleInitialized = true;
    }
    final account = await GoogleSignIn.instance.authenticate();
    final googleIdToken = account.authentication.idToken;
    if (googleIdToken == null || googleIdToken.isEmpty) {
      throw FirebaseAuthException(
        code: 'missing-google-token',
        message: 'Google did not return a valid sign-in token.',
      );
    }
    final credential = GoogleAuthProvider.credential(idToken: googleIdToken);
    final result = await FirebaseAuth.instance.signInWithCredential(credential);
    return _exchange(result.user);
  }

  Future<MartCustomerSession> signInWithPhone({
    required String phoneNumber,
    required Future<String?> Function() requestSmsCode,
  }) async {
    final completer = Completer<UserCredential>();
    await FirebaseAuth.instance.verifyPhoneNumber(
      phoneNumber: _normalizeIndianPhone(phoneNumber),
      timeout: const Duration(seconds: 60),
      verificationCompleted: (credential) async {
        try {
          final result =
              await FirebaseAuth.instance.signInWithCredential(credential);
          if (!completer.isCompleted) completer.complete(result);
        } catch (error, stack) {
          if (!completer.isCompleted) completer.completeError(error, stack);
        }
      },
      verificationFailed: (error) {
        if (!completer.isCompleted) completer.completeError(error);
      },
      codeSent: (verificationId, _) async {
        try {
          final code = await requestSmsCode();
          if (code == null || code.trim().length < 6) {
            throw FirebaseAuthException(
              code: 'otp-cancelled',
              message: 'OTP verification was cancelled.',
            );
          }
          final credential = PhoneAuthProvider.credential(
            verificationId: verificationId,
            smsCode: code.trim(),
          );
          final result =
              await FirebaseAuth.instance.signInWithCredential(credential);
          if (!completer.isCompleted) completer.complete(result);
        } catch (error, stack) {
          if (!completer.isCompleted) completer.completeError(error, stack);
        }
      },
      codeAutoRetrievalTimeout: (_) {},
    );
    final result = await completer.future.timeout(const Duration(minutes: 3));
    return _exchange(result.user);
  }

  Future<void> signOut() async {
    try {
      await FirebaseAuth.instance.signOut();
      if (_googleInitialized) {
        await GoogleSignIn.instance.signOut();
      }
    } catch (_) {
      // Password-only builds do not have a native Firebase app to sign out.
    }
  }

  Future<MartCustomerSession> _exchange(User? user) async {
    final idToken = await user?.getIdToken(true);
    if (idToken == null || idToken.isEmpty) {
      throw FirebaseAuthException(
        code: 'missing-firebase-token',
        message: 'Firebase could not create a secure session.',
      );
    }
    final request = await _http.postUrl(Uri.parse('$_apiRoot/auth/firebase'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.contentType = ContentType.json;
    request.write(jsonEncode({'id_token': idToken}));
    return MartCustomerSession.fromJson(await _decode(await request.close()));
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    final map = decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : <String, dynamic>{};
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw FirebaseAuthException(
        code: 'city-session-failed',
        message: map['message']?.toString() ?? 'Sign-in failed.',
      );
    }
    return map;
  }

  String _normalizeIndianPhone(String input) {
    final digits = input.replaceAll(RegExp(r'\D'), '');
    if (digits.length == 10) return '+91$digits';
    if (digits.length == 12 && digits.startsWith('91')) return '+$digits';
    if (input.trim().startsWith('+') && digits.length >= 10) return '+$digits';
    throw FirebaseAuthException(
      code: 'invalid-phone-number',
      message: 'Enter a valid 10-digit Indian phone number.',
    );
  }
}
