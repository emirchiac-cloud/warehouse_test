package com.emir.warehouse.network;

import android.content.Context;
import com.emir.warehouse.util.SessionManager;

import java.util.concurrent.TimeUnit;

import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // 10.0.2.2 points to localhost from Android Emulator.
    // Can also be dynamically configured via SessionManager.
    public static final String DEFAULT_BASE_URL = "http://10.0.2.2/warehouse/backend-web/";

    private static Retrofit retrofit = null;

    public static synchronized Retrofit getClient(Context context) {
        String baseUrl = DEFAULT_BASE_URL;
        if (context != null) {
            SessionManager session = new SessionManager(context);
            baseUrl = session.getServerUrl();
        }
        if (!baseUrl.endsWith("/")) {
            baseUrl += "/";
        }

        HttpLoggingInterceptor logging = new HttpLoggingInterceptor();
        logging.setLevel(HttpLoggingInterceptor.Level.BODY);

        OkHttpClient okHttpClient = new OkHttpClient.Builder()
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(20, TimeUnit.SECONDS)
                .writeTimeout(20, TimeUnit.SECONDS)
                .addInterceptor(logging)
                .build();

        retrofit = new Retrofit.Builder()
                .baseUrl(baseUrl)
                .addConverterFactory(GsonConverterFactory.create())
                .client(okHttpClient)
                .build();

        return retrofit;
    }

    public static ApiService getService(Context context) {
        return getClient(context).create(ApiService.class);
    }
}
