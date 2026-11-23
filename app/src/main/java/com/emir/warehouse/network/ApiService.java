package com.emir.warehouse.network;

import com.emir.warehouse.model.ApiResponse;
import com.emir.warehouse.model.Category;
import com.emir.warehouse.model.Product;
import com.emir.warehouse.model.User;

import java.util.List;
import java.util.Map;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.DELETE;
import retrofit2.http.Field;
import retrofit2.http.FieldMap;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.GET;
import retrofit2.http.Multipart;
import retrofit2.http.POST;
import retrofit2.http.PUT;
import retrofit2.http.Part;
import retrofit2.http.PartMap;
import retrofit2.http.Query;

public interface ApiService {

    // 1. Authentication
    @POST("api/login.php")
    Call<User> login(@Body Map<String, String> credentials);

    // 2. Fetch Products
    @GET("api/products.php")
    Call<List<Product>> getProducts(
            @Query("category_id") Integer categoryId,
            @Query("search") String search
    );

    // 3. Register New Inventory (Multipart Image Upload)
    @Multipart
    @POST("api/products.php")
    Call<ApiResponse> createProduct(
            @Part MultipartBody.Part image,
            @PartMap Map<String, RequestBody> fields
    );

    // 3b. Register New Inventory without image
    @Multipart
    @POST("api/products.php")
    Call<ApiResponse> createProductNoImage(
            @PartMap Map<String, RequestBody> fields
    );

    // 4. Quick Stock Adjustment (+1 / -1)
    @FormUrlEncoded
    @POST("api/products.php")
    Call<ApiResponse> adjustStock(
            @Field("_method") String method,
            @Field("id") int id,
            @Field("stock_delta") int stockDelta,
            @Field("user_id") int userId,
            @Field("platform") String platform
    );

    // 4b. Full Product Details Update (PUT)
    @FormUrlEncoded
    @POST("api/products.php")
    Call<ApiResponse> updateProductDetails(
            @Field("_method") String method,
            @Field("id") int id,
            @Field("name") String name,
            @Field("sku") String sku,
            @Field("category_id") int categoryId,
            @Field("unit_price") double unitPrice,
            @Field("quantity") int quantity,
            @Field("min_stock_threshold") int minStockThreshold,
            @Field("description") String description,
            @Field("user_id") int userId,
            @Field("platform") String platform
    );

    // 5. Delete Product
    @DELETE("api/products.php")
    Call<ApiResponse> deleteProduct(
            @Query("id") int id,
            @Query("user_id") int userId,
            @Query("platform") String platform
    );

    // 6. Categories List
    @GET("api/categories.php")
    Call<List<Category>> getCategories();
}
