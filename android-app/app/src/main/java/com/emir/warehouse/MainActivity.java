package com.emir.warehouse;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.emir.warehouse.adapter.ProductAdapter;
import com.emir.warehouse.model.Product;
import com.emir.warehouse.network.ApiClient;
import com.emir.warehouse.network.ApiService;
import com.emir.warehouse.util.SessionManager;
import com.google.android.material.floatingactionbutton.FloatingActionButton;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MainActivity extends AppCompatActivity implements ProductAdapter.OnProductClickListener {

    private Toolbar toolbar;
    private TextView tvWelcomeHeader;
    private EditText etSearch;
    private SwipeRefreshLayout swipeRefresh;
    private RecyclerView recyclerViewProducts;
    private ProgressBar loadingIndicator;
    private TextView tvEmptyState;
    private FloatingActionButton fabAddProduct;

    private ProductAdapter adapter;
    private SessionManager sessionManager;
    private List<Product> allProducts = new ArrayList<>();

    // Activity Result Launcher to refresh list after Add/Detail/Delete
    private final ActivityResultLauncher<Intent> productActivityLauncher =
            registerForActivityResult(new ActivityResultContracts.StartActivityForResult(), result -> {
                loadProducts(null);
            });

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        sessionManager = new SessionManager(this);

        // Check Notification Permission on Android 13+
        requestNotificationPermission();

        // Bind Views
        toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        tvWelcomeHeader = findViewById(R.id.tvWelcomeHeader);
        etSearch = findViewById(R.id.etSearch);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        recyclerViewProducts = findViewById(R.id.recyclerViewProducts);
        loadingIndicator = findViewById(R.id.loadingIndicator);
        tvEmptyState = findViewById(R.id.tvEmptyState);
        fabAddProduct = findViewById(R.id.fabAddProduct);

        // Display personalized welcome header from Intent Extras or SharedPreferences
        String userName = getIntent().getStringExtra("EXTRA_USER_NAME");
        String userRole = getIntent().getStringExtra("EXTRA_USER_ROLE");
        if (userName == null || userName.isEmpty()) {
            userName = sessionManager.getUserName();
            userRole = sessionManager.getUserRole();
        }
        String formattedRole = (userRole != null && !userRole.isEmpty()) ? 
                Character.toUpperCase(userRole.charAt(0)) + userRole.substring(1) : "Staff";
        tvWelcomeHeader.setText(String.format("Welcome, %s (%s)", userName, formattedRole));

        // Setup RecyclerView
        recyclerViewProducts.setLayoutManager(new LinearLayoutManager(this));
        adapter = new ProductAdapter(this, this);
        recyclerViewProducts.setAdapter(adapter);

        // Pull to refresh listener
        swipeRefresh.setOnRefreshListener(() -> loadProducts(etSearch.getText().toString().trim()));

        // Search text watcher
        etSearch.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                filterProducts(s.toString());
            }
            @Override
            public void afterTextChanged(Editable s) {}
        });

        // FAB to register new inventory (Workflow 3)
        fabAddProduct.setOnClickListener(v -> {
            Intent intent = new Intent(MainActivity.this, AddProductActivity.class);
            productActivityLauncher.launch(intent);
        });

        // Load initial products from API
        loadProducts(null);
    }

    private void requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, 101);
            }
        }
    }

    /**
     * Fetch products asynchronously via Retrofit (GET) and bind the parsed JSON list to RecyclerView
     */
    private void loadProducts(String searchQuery) {
        loadingIndicator.setVisibility(View.VISIBLE);
        tvEmptyState.setVisibility(View.GONE);

        ApiService apiService = ApiClient.getService(this);
        apiService.getProducts(null, searchQuery).enqueue(new Callback<List<Product>>() {
            @Override
            public void onResponse(Call<List<Product>> call, Response<List<Product>> response) {
                loadingIndicator.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);

                if (response.isSuccessful() && response.body() != null) {
                    allProducts = response.body();
                    adapter.setProducts(allProducts);

                    if (allProducts.isEmpty()) {
                        tvEmptyState.setVisibility(View.VISIBLE);
                    } else {
                        tvEmptyState.setVisibility(View.GONE);
                    }
                } else {
                    Toast.makeText(MainActivity.this, "Failed to load products: " + response.code(), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<List<Product>> call, Throwable t) {
                loadingIndicator.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);
                Toast.makeText(MainActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
                if (allProducts.isEmpty()) {
                    tvEmptyState.setVisibility(View.VISIBLE);
                }
            }
        });
    }

    private void filterProducts(String query) {
        if (query == null || query.trim().isEmpty()) {
            adapter.setProducts(allProducts);
            return;
        }
        String lower = query.toLowerCase().trim();
        List<Product> filtered = new ArrayList<>();
        for (Product p : allProducts) {
            if ((p.getName() != null && p.getName().toLowerCase().contains(lower)) ||
                (p.getSku() != null && p.getSku().toLowerCase().contains(lower)) ||
                (p.getCategoryName() != null && p.getCategoryName().toLowerCase().contains(lower))) {
                filtered.add(p);
            }
        }
        adapter.setProducts(filtered);
    }

    /**
     * Tapping any item in the RecyclerView must launch Product Detail / Edit Activity
     * using an explicit Intent carrying the selected product's data (Workflow 4)
     */
    @Override
    public void onProductClick(Product product) {
        Intent intent = new Intent(MainActivity.this, ProductDetailActivity.class);
        intent.putExtra("EXTRA_PRODUCT", product);
        productActivityLauncher.launch(intent);
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.main_menu, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        int id = item.getItemId();
        if (id == R.id.action_refresh) {
            swipeRefresh.setRefreshing(true);
            loadProducts(etSearch.getText().toString().trim());
            return true;
        } else if (id == R.id.action_server) {
            showServerConfigDialog();
            return true;
        } else if (id == R.id.action_logout) {
            performLogout();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    private void performLogout() {
        new AlertDialog.Builder(this)
                .setTitle("Confirm Logout")
                .setMessage("Are you sure you want to end your session?")
                .setPositiveButton("Logout", (dialog, which) -> {
                    // Clears SharedPreferences and returns to Login
                    sessionManager.logout();
                    Intent intent = new Intent(MainActivity.this, LoginActivity.class);
                    intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                    startActivity(intent);
                    finish();
                })
                .setNegativeButton("Cancel", null)
                .show();
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
                loadProducts(null);
                Toast.makeText(this, "Server URL updated", Toast.LENGTH_SHORT).show();
            }
        });
        builder.setNegativeButton("Cancel", null);
        builder.show();
    }
}
