package com.emir.warehouse;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;

import com.emir.warehouse.model.User;
import com.emir.warehouse.network.ApiClient;
import com.emir.warehouse.network.ApiService;
import com.emir.warehouse.util.SessionManager;
import com.google.android.material.button.MaterialButton;

import java.util.HashMap;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {

    private EditText etEmail;
    private EditText etPassword;
    private MaterialButton btnLogin;
    private ProgressBar progressBar;
    private TextView tvServerConfig;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        sessionManager = new SessionManager(this);

        // Auto-Login: Check SharedPreferences. If valid session exists, bypass Login screen.
        if (sessionManager.isLoggedIn()) {
            navigateToDashboard(sessionManager.getUserName(), sessionManager.getUserRole());
            return;
        }

        setContentView(R.layout.activity_login);

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        tvServerConfig = findViewById(R.id.tvServerConfig);

        btnLogin.setOnClickListener(v -> performLogin());

        tvServerConfig.setOnClickListener(v -> showServerConfigDialog());
    }

    private void performLogin() {
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Please enter email and password", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnLogin.setEnabled(false);

        ApiService apiService = ApiClient.getService(this);
        Map<String, String> credentials = new HashMap<>();
        credentials.put("email", email);
        credentials.put("password", password);

        apiService.login(credentials).enqueue(new Callback<User>() {
            @Override
            public void onResponse(Call<User> call, Response<User> response) {
                progressBar.setVisibility(View.GONE);
                btnLogin.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    User user = response.body();
                    // Save user's id, full_name, email, and role in SharedPreferences
                    sessionManager.saveUser(user);

                    Toast.makeText(LoginActivity.this, "Welcome " + user.getFullName(), Toast.LENGTH_SHORT).show();
                    navigateToDashboard(user.getFullName(), user.getRole());
                } else {
                    String errorMsg = "Login failed: Invalid credentials.";
                    if (response.code() == 401) {
                        errorMsg = "HTTP 401: Invalid email or password.";
                    }
                    Toast.makeText(LoginActivity.this, errorMsg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<User> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnLogin.setEnabled(true);
                Toast.makeText(LoginActivity.this, "Connection Error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void navigateToDashboard(String name, String role) {
        Intent intent = new Intent(LoginActivity.this, MainActivity.class);
        // Pass the logged-in user's name and role via Intent extras
        intent.putExtra("EXTRA_USER_NAME", name);
        intent.putExtra("EXTRA_USER_ROLE", role);
        startActivity(intent);
        finish();
    }

    private void showServerConfigDialog() {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        builder.setTitle("Server Backend URL");

        final EditText input = new EditText(this);
        input.setText(sessionManager.getServerUrl());
        input.setPadding(32, 24, 32, 24);
        builder.setView(input);

        builder.setPositiveButton("Save", (dialog, which) -> {
            String url = input.getText().toString().trim();
            if (!url.isEmpty()) {
                sessionManager.setServerUrl(url);
                Toast.makeText(this, "Server URL updated", Toast.LENGTH_SHORT).show();
            }
        });
        builder.setNegativeButton("Cancel", (dialog, which) -> dialog.cancel());
        builder.show();
    }
}
