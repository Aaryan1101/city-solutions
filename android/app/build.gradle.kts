import java.io.FileInputStream
import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val hasGoogleServicesConfig = listOf(
    file("google-services.json"),
    file("src/customer/google-services.json"),
    file("src/worker/google-services.json"),
).any { it.exists() }
if (hasGoogleServicesConfig) {
    apply(plugin = "com.google.gms.google-services")
}

val secretsProperties = Properties()
val secretsFile = rootProject.file("secrets.properties")
if (secretsFile.exists()) {
    FileInputStream(secretsFile).use(secretsProperties::load)
}
val mapsApiKey = secretsProperties.getProperty("MAPS_API_KEY")
    ?: System.getenv("MAPS_API_KEY")
    ?: ""

android {
    namespace = "com.citysolutions.app"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = "28.2.13676358"
    flavorDimensions += "app"

    compileOptions {
        isCoreLibraryDesugaringEnabled = true
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    buildTypes {
        release {
            // Replace this with a private release signing config before publishing.
            // Debug signing keeps local release-mode builds runnable during development.
            signingConfig = signingConfigs.getByName("debug")
        }
    }

    defaultConfig {
        applicationId = "com.citysolutions.app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
        manifestPlaceholders["appLabel"] = "City Solutions"
        manifestPlaceholders["mapsApiKey"] = mapsApiKey
    }

    buildFeatures {
        buildConfig = true
    }

    productFlavors {
        create("customer") {
            dimension = "app"
            applicationId = "com.citysolutions.app"
            manifestPlaceholders["appLabel"] = "City Solutions"
        }
        create("worker") {
            dimension = "app"
            applicationId = "com.citysolutions.worker"
            manifestPlaceholders["appLabel"] = "City Worker"
        }
    }

}

dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.5")
}

flutter {
    source = "../.."
}
