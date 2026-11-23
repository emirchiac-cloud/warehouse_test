package com.emir.warehouse.model;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class Product implements Serializable {
    @SerializedName("id")
    private int id;

    @SerializedName("category_id")
    private int categoryId;

    @SerializedName("category_name")
    private String categoryName;

    @SerializedName("sku")
    private String sku;

    @SerializedName("name")
    private String name;

    @SerializedName("description")
    private String description;

    @SerializedName("unit_price")
    private double unitPrice;

    @SerializedName("quantity")
    private int quantity;

    @SerializedName("min_stock_threshold")
    private int minStockThreshold;

    @SerializedName("image_path")
    private String imagePath;

    @SerializedName("image_url")
    private String imageUrl;

    @SerializedName("is_low_stock")
    private boolean isLowStock;

    @SerializedName("updated_at")
    private String updatedAt;

    public Product() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getCategoryId() { return categoryId; }
    public void setCategoryId(int categoryId) { this.categoryId = categoryId; }

    public String getCategoryName() { return categoryName; }
    public void setCategoryName(String categoryName) { this.categoryName = categoryName; }

    public String getSku() { return sku; }
    public void setSku(String sku) { this.sku = sku; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public double getUnitPrice() { return unitPrice; }
    public void setUnitPrice(double unitPrice) { this.unitPrice = unitPrice; }

    public int getQuantity() { return quantity; }
    public void setQuantity(int quantity) { 
        this.quantity = quantity;
        this.isLowStock = (this.quantity < this.minStockThreshold);
    }

    public int getMinStockThreshold() { return minStockThreshold; }
    public void setMinStockThreshold(int minStockThreshold) { 
        this.minStockThreshold = minStockThreshold;
        this.isLowStock = (this.quantity < this.minStockThreshold);
    }

    public String getImagePath() { return imagePath; }
    public void setImagePath(String imagePath) { this.imagePath = imagePath; }

    public String getImageUrl() { return imageUrl; }
    public void setImageUrl(String imageUrl) { this.imageUrl = imageUrl; }

    public boolean isLowStock() { 
        return isLowStock || (quantity < minStockThreshold); 
    }
    public void setLowStock(boolean lowStock) { isLowStock = lowStock; }

    public String getUpdatedAt() { return updatedAt; }
    public void setUpdatedAt(String updatedAt) { this.updatedAt = updatedAt; }
}
