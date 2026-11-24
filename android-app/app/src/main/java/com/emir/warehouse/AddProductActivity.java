package com.emir.warehouse;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import com.emir.warehouse.model.ApiResponse;
import com.emir.warehouse.model.Category;
import com.emir.warehouse.network.ApiClient;
import com.emir.warehouse.network.ApiService;
import com.emir.warehouse.util.SessionManager;
import com.google.android.material.button.MaterialButton;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * [SPEAKER 4 - WORKFLOW 3: New Inventory Registration with Camera/Gallery Photo Capture]
 * Handles product creation form with camera/gallery image picker and multipart Retrofit upload.
 */
public class AddProductActivity extends AppCompatActivity {

    private ImageView imgPreview;
    private MaterialButton btnCaptureCamera;
    private MaterialButton btnPickGallery;
    private TextView tvImageStatus;

    private EditText etName;
    private EditText etSku;
    private Spinner spinnerCategory;
    private EditText etPrice;
    private EditText etQuantity;
    private EditText etMinThreshold;
    private EditText etDescription;
    private MaterialButton btnSubmit;
    private ProgressBar uploadProgress;

    private Uri currentPhotoUri = null;
    private File selectedImageFile = null;
    private List<Category> categoriesList = new ArrayList<>();
    private SessionManager sessionManager;

    // Camera Launcher
    private final ActivityResultLauncher<Uri> cameraLauncher =
            registerForActivityResult(new ActivityResultContracts.TakePicture(), success -> {
                if (success && currentPhotoUri != null) {
                    imgPreview.setImageURI(currentPhotoUri);
                    tvImageStatus.setText("Photo captured via camera");
                    // Convert Uri to local file for multipart upload
                    selectedImageFile = getFileFromUri(currentPhotoUri);
                }
            });

    // Gallery Launcher
    private final ActivityResultLauncher<String> galleryLauncher =
            registerForActivityResult(new ActivityResultContracts.GetContent(), uri -> {
                if (uri != null) {
                    currentPhotoUri = uri;
                    imgPreview.setImageURI(uri);
                    tvImageStatus.setText("Photo selected from gallery");
                    selectedImageFile = getFileFromUri(uri);
                }
            });

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_product);

        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Register Inventory");
        }

        sessionManager = new SessionManager(this);

        // Bind Views
        imgPreview = findViewById(R.id.imgPreview);
        btnCaptureCamera = findViewById(R.id.btnCaptureCamera);
        btnPickGallery = findViewById(R.id.btnPickGallery);
        tvImageStatus = findViewById(R.id.tvImageStatus);

        etName = findViewById(R.id.etName);
        etSku = findViewById(R.id.etSku);
        spinnerCategory = findViewById(R.id.spinnerCategory);
        etPrice = findViewById(R.id.etPrice);
        etQuantity = findViewById(R.id.etQuantity);
        etMinThreshold = findViewById(R.id.etMinThreshold);
        etDescription = findViewById(R.id.etDescription);
        btnSubmit = findViewById(R.id.btnSubmit);
        MaterialButton btnBack = findViewById(R.id.btnBack);
        uploadProgress = findViewById(R.id.uploadProgress);

        btnCaptureCamera.setOnClickListener(v -> dispatchTakePictureIntent());
        btnPickGallery.setOnClickListener(v -> galleryLauncher.launch("image/*"));
        btnSubmit.setOnClickListener(v -> submitNewProduct());
        btnBack.setOnClickListener(v -> finish());

        loadCategories();
    }

    private void loadCategories() {
        ApiService apiService = ApiClient.getService(this);
        apiService.getCategories().enqueue(new Callback<List<Category>>() {
            @Override
            public void onResponse(Call<List<Category>> call, Response<List<Category>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    categoriesList = response.body();
                    ArrayAdapter<Category> adapter = new ArrayAdapter<>(
                            AddProductActivity.this,
                            android.R.layout.simple_spinner_item,
                            categoriesList
                    );
                    adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerCategory.setAdapter(adapter);
                }
            }

            @Override
            public void onFailure(Call<List<Category>> call, Throwable t) {
                Toast.makeText(AddProductActivity.this, "Could not load categories", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void dispatchTakePictureIntent() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.CAMERA}, 102);
            return;
        }

        try {
            File storageDir = getExternalFilesDir(Environment.DIRECTORY_PICTURES);
            File photoFile = File.createTempFile("prod_" + System.currentTimeMillis(), ".jpg", storageDir);
            currentPhotoUri = FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", photoFile);
            cameraLauncher.launch(currentPhotoUri);
        } catch (IOException e) {
            Toast.makeText(this, "Failed to create camera file", Toast.LENGTH_SHORT).show();
        }
    }

    private File getFileFromUri(Uri uri) {
        try {
            InputStream inputStream = getContentResolver().openInputStream(uri);
            if (inputStream == null) return null;

            File tempFile = new File(getCacheDir(), "upload_" + System.currentTimeMillis() + ".jpg");
            FileOutputStream outputStream = new FileOutputStream(tempFile);

            // Compress image to ensure under 5MB limit
            Bitmap bitmap = BitmapFactory.decodeStream(inputStream);
            bitmap.compress(Bitmap.CompressFormat.JPEG, 85, outputStream);

            outputStream.flush();
            outputStream.close();
            inputStream.close();
            return tempFile;
        } catch (Exception e) {
            return null;
        }
    }

    private void submitNewProduct() {
        String name = etName.getText().toString().trim();
        String sku = etSku.getText().toString().trim();
        String priceStr = etPrice.getText().toString().trim();
        String qtyStr = etQuantity.getText().toString().trim();
        String minStr = etMinThreshold.getText().toString().trim();
        String desc = etDescription.getText().toString().trim();

        if (name.isEmpty() || sku.isEmpty() || priceStr.isEmpty() || qtyStr.isEmpty() || minStr.isEmpty()) {
            Toast.makeText(this, "Please fill in all required fields (*)", Toast.LENGTH_SHORT).show();
            return;
        }

        if (spinnerCategory.getSelectedItem() == null) {
            Toast.makeText(this, "Please select a category", Toast.LENGTH_SHORT).show();
            return;
        }

        Category selectedCat = (Category) spinnerCategory.getSelectedItem();

        uploadProgress.setVisibility(View.VISIBLE);
        btnSubmit.setEnabled(false);

        // Build Text RequestBody fields
        Map<String, RequestBody> fields = new HashMap<>();
        MediaType textPlain = MediaType.parse("text/plain");

        fields.put("name", RequestBody.create(textPlain, name));
        fields.put("sku", RequestBody.create(textPlain, sku));
        fields.put("category_id", RequestBody.create(textPlain, String.valueOf(selectedCat.getId())));
        fields.put("unit_price", RequestBody.create(textPlain, priceStr));
        fields.put("quantity", RequestBody.create(textPlain, qtyStr));
        fields.put("min_stock_threshold", RequestBody.create(textPlain, minStr));
        fields.put("description", RequestBody.create(textPlain, desc));
        fields.put("user_id", RequestBody.create(textPlain, String.valueOf(sessionManager.getUserId())));
        fields.put("platform", RequestBody.create(textPlain, "MOBILE"));

        ApiService apiService = ApiClient.getService(this);
        Call<ApiResponse> call;

        if (selectedImageFile != null && selectedImageFile.exists()) {
            RequestBody requestFile = RequestBody.create(MediaType.parse("image/jpeg"), selectedImageFile);
            MultipartBody.Part imagePart = MultipartBody.Part.createFormData("image", selectedImageFile.getName(), requestFile);
            call = apiService.createProduct(imagePart, fields);
        } else {
            call = apiService.createProductNoImage(fields);
        }

        call.enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                uploadProgress.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(AddProductActivity.this, "Product registered successfully!", Toast.LENGTH_LONG).show();
                    setResult(RESULT_OK);
                    finish();
                } else {
                    String err = "Upload failed: " + response.code();
                    if (response.body() != null && response.body().getMessage() != null) {
                        err = response.body().getMessage();
                    }
                    Toast.makeText(AddProductActivity.this, err, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                uploadProgress.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);
                Toast.makeText(AddProductActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}
