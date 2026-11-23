package com.emir.warehouse.util;

import android.content.Context;
import android.content.SharedPreferences;
import com.emir.warehouse.model.User;
import com.emir.warehouse.network.ApiClient;

public class SessionManager {
    private static final String PREF_NAME = "warehouse_session";
    private static final String KEY_IS_LOGGED_IN = "is_logged_in";
    private static final String KEY_USER_ID = "user_id";
    private static final String KEY_FULL_NAME = "full_name";
    private static final String KEY_EMAIL = "email";
    private static final String KEY_ROLE = "role";
    private static final String KEY_SERVER_URL = "server_url";

    private final SharedPreferences prefs;
    private final SharedPreferences.Editor editor;

    public SessionManager(Context context) {
        prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        editor = prefs.edit();
    }

    public void saveUser(User user) {
        editor.putBoolean(KEY_IS_LOGGED_IN, true);
        editor.putInt(KEY_USER_ID, user.getId());
        editor.putString(KEY_FULL_NAME, user.getFullName());
        editor.putString(KEY_EMAIL, user.getEmail());
        editor.putString(KEY_ROLE, user.getRole());
        editor.apply();
    }

    public boolean isLoggedIn() {
        return prefs.getBoolean(KEY_IS_LOGGED_IN, false);
    }

    public User getUser() {
        if (!isLoggedIn()) return null;
        User user = new User();
        user.setId(prefs.getInt(KEY_USER_ID, 0));
        user.setFullName(prefs.getString(KEY_FULL_NAME, "Staff"));
        user.setEmail(prefs.getString(KEY_EMAIL, ""));
        user.setRole(prefs.getString(KEY_ROLE, "staff"));
        return user;
    }

    public int getUserId() {
        return prefs.getInt(KEY_USER_ID, 1);
    }

    public String getUserName() {
        return prefs.getString(KEY_FULL_NAME, "Staff");
    }

    public String getUserRole() {
        return prefs.getString(KEY_ROLE, "staff");
    }

    public String getServerUrl() {
        return prefs.getString(KEY_SERVER_URL, ApiClient.DEFAULT_BASE_URL);
    }

    public void setServerUrl(String url) {
        editor.putString(KEY_SERVER_URL, url);
        editor.apply();
    }

    public void logout() {
        String savedServer = getServerUrl();
        editor.clear();
        editor.putString(KEY_SERVER_URL, savedServer);
        editor.apply();
    }
}
