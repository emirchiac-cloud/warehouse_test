package com.emir.warehouse.adapter;

import android.content.Context;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.emir.warehouse.R;
import com.emir.warehouse.model.Product;
import com.google.android.material.card.MaterialCardView;
import com.squareup.picasso.Picasso;

import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

public class ProductAdapter extends RecyclerView.Adapter<ProductAdapter.ProductViewHolder> {

    public interface OnProductClickListener {
        void onProductClick(Product product);
    }

    private final Context context;
    private List<Product> productList = new ArrayList<>();
    private final OnProductClickListener listener;

    public ProductAdapter(Context context, OnProductClickListener listener) {
        this.context = context;
        this.listener = listener;
    }

    public void setProducts(List<Product> products) {
        this.productList = (products != null) ? products : new ArrayList<>();
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ProductViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_product, parent, false);
        return new ProductViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ProductViewHolder holder, int position) {
        Product p = productList.get(position);

        holder.tvProductName.setText(p.getName());
        holder.tvProductMeta.setText(String.format("%s • SKU: %s", 
                (p.getCategoryName() != null ? p.getCategoryName() : "General"), p.getSku()));
        holder.tvProductPrice.setText(String.format(Locale.US, "$%.2f", p.getUnitPrice()));
        holder.tvStockCount.setText(String.format(Locale.US, "Stock: %d (Min: %d)", p.getQuantity(), p.getMinStockThreshold()));

        // Asynchronous Picasso Image Loading with required placeholder, error, resize, and centerCrop
        String imgUrl = p.getImageUrl();
        if (imgUrl != null && !imgUrl.trim().isEmpty()) {
            Picasso.get()
                    .load(imgUrl)
                    .placeholder(R.drawable.image_placeholder)
                    .error(R.drawable.image_error)
                    .resize(160, 160)
                    .centerCrop()
                    .into(holder.imgProductThumb);
        } else {
            holder.imgProductThumb.setImageResource(R.drawable.image_placeholder);
        }

        // Visually distinguish low-stock items (red badge or tinted border when quantity < min_stock_threshold)
        boolean isLow = p.isLowStock();
        if (isLow) {
            holder.tvStockStatusBadge.setVisibility(View.VISIBLE);
            holder.tvStockStatusBadge.setText("LOW STOCK");
            holder.tvStockStatusBadge.setBackgroundResource(R.drawable.badge_low_stock);
            
            // Tinted border and background for low-stock alert
            holder.cardProduct.setStrokeColor(ContextCompat.getColor(context, R.color.stock_low));
            holder.cardProduct.setStrokeWidth(4);
            holder.cardProduct.setCardBackgroundColor(ContextCompat.getColor(context, R.color.stock_low_bg));
        } else {
            holder.tvStockStatusBadge.setVisibility(View.VISIBLE);
            holder.tvStockStatusBadge.setText("IN STOCK");
            holder.tvStockStatusBadge.setBackgroundResource(R.drawable.badge_in_stock);

            holder.cardProduct.setStrokeColor(ContextCompat.getColor(context, R.color.card_border));
            holder.cardProduct.setStrokeWidth(2);
            holder.cardProduct.setCardBackgroundColor(Color.WHITE);
        }

        // Tap listener
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onProductClick(p);
            }
        });
    }

    @Override
    public int getItemCount() {
        return productList.size();
    }

    static class ProductViewHolder extends RecyclerView.ViewHolder {
        final MaterialCardView cardProduct;
        final ImageView imgProductThumb;
        final TextView tvProductName;
        final TextView tvProductMeta;
        final TextView tvProductPrice;
        final TextView tvStockCount;
        final TextView tvStockStatusBadge;

        public ProductViewHolder(@NonNull View itemView) {
            super(itemView);
            cardProduct = itemView.findViewById(R.id.cardProduct);
            imgProductThumb = itemView.findViewById(R.id.imgProductThumb);
            tvProductName = itemView.findViewById(R.id.tvProductName);
            tvProductMeta = itemView.findViewById(R.id.tvProductMeta);
            tvProductPrice = itemView.findViewById(R.id.tvProductPrice);
            tvStockCount = itemView.findViewById(R.id.tvStockCount);
            tvStockStatusBadge = itemView.findViewById(R.id.tvStockStatusBadge);
        }
    }
}
