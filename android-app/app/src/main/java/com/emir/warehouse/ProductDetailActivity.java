package com.emir.warehouse;

import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import com.emir.warehouse.model.ApiResponse;
import com.emir.warehouse.model.Product;
import com.emir.warehouse.network.ApiClient;
import com.emir.warehouse.network.ApiService;
import com.emir.warehouse.util.HardwareAlertHelper;
import com.emir.warehouse.util.SessionManager;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;
import com.google.android.material.textfield.TextInputEditText;
import com.squareup.picasso.Picasso;

import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProductDetailActivity extends AppCompatActivity {

    private ImageView imgDetail;
    private TextView tvDetailName;
    private TextView tvDetailStatusBadge;
    private TextView tvDetailCategorySku;
    private MaterialCardView cardStockControl;
    private TextView tvDetailQuantity;
    private TextView tvThresholdNote;
    private MaterialButton btnQuickMinus;
    private MaterialButton btnQuickPlus;

    private TextInputEditText etDetailPrice;
    private TextInputEditText etDetailThreshold;
    private TextInputEditText etDetailDesc;
    private MaterialButton btnSaveUpdate;
    private MaterialButton btnDeleteProduct;
    private MaterialButton btnBack;
    private ProgressBar detailProgress;

    private Product product;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_product_detail);

        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Product Details");
        }

        sessionManager = new SessionManager(this);

        // Retrieve product passed via explicit Intent extra
        product = (Product) getIntent().getSerializableExtra("EXTRA_PRODUCT");
        if (product == null) {
            Toast.makeText(this, "Product data not found", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // Bind Views
        imgDetail = findViewById(R.id.imgDetail);
        tvDetailName = findViewById(R.id.tvDetailName);
        tvDetailStatusBadge = findViewById(R.id.tvDetailStatusBadge);
        tvDetailCategorySku = findViewById(R.id.tvDetailCategorySku);
        cardStockControl = findViewById(R.id.cardStockControl);
        tvDetailQuantity = findViewById(R.id.tvDetailQuantity);
        tvThresholdNote = findViewById(R.id.tvThresholdNote);
        btnQuickMinus = findViewById(R.id.btnQuickMinus);
        btnQuickPlus = findViewById(R.id.btnQuickPlus);

        etDetailPrice = findViewById(R.id.etDetailPrice);
        etDetailThreshold = findViewById(R.id.etDetailThreshold);
        etDetailDesc = findViewById(R.id.etDetailDesc);
        btnSaveUpdate = findViewById(R.id.btnSaveUpdate);
        btnDeleteProduct = findViewById(R.id.btnDeleteProduct);
        btnBack = findViewById(R.id.btnBack);
        detailProgress = findViewById(R.id.detailProgress);

        // Populate Views with initial Product details
        bindProductData();

        // Listeners for quick stock adjustments
        btnQuickMinus.setOnClickListener(v -> adjustStockDelta(-1));
        btnQuickPlus.setOnClickListener(v -> adjustStockDelta(1));

        // Listener for Save/Update details
        btnSaveUpdate.setOnClickListener(v -> updateProductDetails());

        // Listener for Delete Product with AlertDialog confirmation
        btnDeleteProduct.setOnClickListener(v -> confirmDeleteProduct());

        // Listener for Back button
        btnBack.setOnClickListener(v -> {
            setResult(RESULT_OK);
            finish();
        });
    }

    private void bindProductData() {
        tvDetailName.setText(product.getName());
        tvDetailCategorySku.setText(String.format("%s • SKU: %s", 
                product.getCategoryName() != null ? product.getCategoryName() : "General", 
                product.getSku()));
        tvDetailQuantity.setText(String.valueOf(product.getQuantity()));
        tvThresholdNote.setText(String.format(Locale.US, "Safe Minimum Threshold: %d units", product.getMinStockThreshold()));

        etDetailPrice.setText(String.format(Locale.US, "%.2f", product.getUnitPrice()));
        etDetailThreshold.setText(String.valueOf(product.getMinStockThreshold()));
        etDetailDesc.setText(product.getDescription() != null ? product.getDescription() : "");

        updateStatusBadges();

        // Picasso image loading
        String imgUrl = product.getImageUrl();
        if (imgUrl != null && !imgUrl.trim().isEmpty()) {
            Picasso.get()
                    .load(imgUrl)
                    .placeholder(R.drawable.image_placeholder)
                    .error(R.drawable.image_error)
                    .resize(400, 300)
                    .centerCrop()
                    .into(imgDetail);
        } else {
            imgDetail.setImageResource(R.drawable.image_placeholder);
        }
    }

    private void updateStatusBadges() {
        boolean isLow = product.isLowStock();
        if (isLow) {
            tvDetailStatusBadge.setText("LOW STOCK");
            tvDetailStatusBadge.setBackgroundResource(R.drawable.badge_low_stock);
            cardStockControl.setStrokeColor(ContextCompat.getColor(this, R.color.stock_low));
            cardStockControl.setCardBackgroundColor(ContextCompat.getColor(this, R.color.stock_low_bg));
        } else {
            tvDetailStatusBadge.setText("IN STOCK");
            tvDetailStatusBadge.setBackgroundResource(R.drawable.badge_in_stock);
            cardStockControl.setStrokeColor(ContextCompat.getColor(this, R.color.card_border));
            cardStockControl.setCardBackgroundColor(Color.WHITE);
        }
    }

    /**
     * Quick +1 and -1 stock adjustment buttons that send an asynchronous PUT request
     * to update the stock count in MySQL (Workflow 4)
     */
    private void adjustStockDelta(int delta) {
        int previousQty = product.getQuantity();
        int targetQty = previousQty + delta;
        if (targetQty < 0) {
            Toast.makeText(this, "Stock count cannot be negative", Toast.LENGTH_SHORT).show();
            return;
        }

        detailProgress.setVisibility(View.VISIBLE);
        setButtonsEnabled(false);

        ApiService apiService = ApiClient.getService(this);
        apiService.adjustStock(
                "PUT",
                product.getId(),
                delta,
                sessionManager.getUserId(),
                "MOBILE"
        ).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                detailProgress.setVisibility(View.GONE);
                setButtonsEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    int newQty = response.body().getQuantity();
                    product.setQuantity(newQty);
                    tvDetailQuantity.setText(String.valueOf(newQty));
                    updateStatusBadges();

                    Toast.makeText(ProductDetailActivity.this, "Stock updated to " + newQty, Toast.LENGTH_SHORT).show();

                    // Workflow 5: Hardware Feedback & Low-Stock Alerts
                    // Whenever a stock adjustment causes a product's quantity to drop below its min_stock_threshold:
                    // 1. Trigger the Android Vibrator service for 500ms
                    // 2. Fire a local Android System Notification
                    if (newQty < product.getMinStockThreshold()) {
                        HardwareAlertHelper.triggerLowStockAlert(ProductDetailActivity.this, product);
                    }
                } else {
                    Toast.makeText(ProductDetailActivity.this, "Failed to update stock", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                detailProgress.setVisibility(View.GONE);
                setButtonsEnabled(true);
                Toast.makeText(ProductDetailActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    /**
     * Save/Update button sending PUT request for full details
     */
    private void updateProductDetails() {
        String priceStr = etDetailPrice.getText().toString().trim();
        String minStr = etDetailThreshold.getText().toString().trim();
        String desc = etDetailDesc.getText().toString().trim();

        if (priceStr.isEmpty() || minStr.isEmpty()) {
            Toast.makeText(this, "Price and Threshold cannot be empty", Toast.LENGTH_SHORT).show();
            return;
        }

        double unitPrice = Double.parseDouble(priceStr);
        int minThreshold = Integer.parseInt(minStr);

        detailProgress.setVisibility(View.VISIBLE);
        setButtonsEnabled(false);

        ApiService apiService = ApiClient.getService(this);
        apiService.updateProductDetails(
                "PUT",
                product.getId(),
                product.getName(),
                product.getSku(),
                product.getCategoryId(),
                unitPrice,
                product.getQuantity(),
                minThreshold,
                desc,
                sessionManager.getUserId(),
                "MOBILE"
        ).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                detailProgress.setVisibility(View.GONE);
                setButtonsEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    product.setUnitPrice(unitPrice);
                    product.setMinStockThreshold(minThreshold);
                    product.setDescription(desc);
                    tvThresholdNote.setText(String.format(Locale.US, "Safe Minimum Threshold: %d units", minThreshold));
                    updateStatusBadges();

                    Toast.makeText(ProductDetailActivity.this, "Product details updated successfully!", Toast.LENGTH_SHORT).show();

                    // Check low-stock alert
                    if (product.getQuantity() < minThreshold) {
                        HardwareAlertHelper.triggerLowStockAlert(ProductDetailActivity.this, product);
                    }

                    setResult(RESULT_OK);
                    finish();
                } else {
                    Toast.makeText(ProductDetailActivity.this, "Failed to update details", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                detailProgress.setVisibility(View.GONE);
                setButtonsEnabled(true);
                Toast.makeText(ProductDetailActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    /**
     * Delete Product button with confirmation AlertDialog (Workflow 4)
     */
    private void confirmDeleteProduct() {
        new AlertDialog.Builder(this)
                .setTitle("Delete Product")
                .setMessage("Are you sure you want to delete \"" + product.getName() + "\"? This action cannot be undone.")
                .setIcon(android.R.drawable.ic_dialog_alert)
                .setPositiveButton("Delete", (dialog, which) -> executeDelete())
                .setNegativeButton("Cancel", null)
                .show();
    }

    private void executeDelete() {
        detailProgress.setVisibility(View.VISIBLE);
        setButtonsEnabled(false);

        ApiService apiService = ApiClient.getService(this);
        apiService.deleteProduct(product.getId(), sessionManager.getUserId(), "MOBILE")
                .enqueue(new Callback<ApiResponse>() {
                    @Override
                    public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                        detailProgress.setVisibility(View.GONE);
                        setButtonsEnabled(true);

                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            Toast.makeText(ProductDetailActivity.this, "Product deleted successfully.", Toast.LENGTH_SHORT).show();
                            // Refreshes the list upon return
                            setResult(RESULT_OK);
                            finish();
                        } else {
                            Toast.makeText(ProductDetailActivity.this, "Failed to delete product.", Toast.LENGTH_SHORT).show();
                        }
                    }

                    @Override
                    public void onFailure(Call<ApiResponse> call, Throwable t) {
                        detailProgress.setVisibility(View.GONE);
                        setButtonsEnabled(true);
                        Toast.makeText(ProductDetailActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
                    }
                });
    }

    private void setButtonsEnabled(boolean enabled) {
        btnQuickMinus.setEnabled(enabled);
        btnQuickPlus.setEnabled(enabled);
        btnSaveUpdate.setEnabled(enabled);
        btnDeleteProduct.setEnabled(enabled);
        btnBack.setEnabled(enabled);
    }

    @Override
    public boolean onSupportNavigateUp() {
        setResult(RESULT_OK);
        finish();
        return true;
    }
}
