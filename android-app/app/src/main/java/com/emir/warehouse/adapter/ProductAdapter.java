package com.emir.warehouse.adapter;

import android.animation.ObjectAnimator;
import android.content.Context;
import android.graphics.Color;
import android.provider.Settings;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.ProgressBar;
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
        void onPlusClick(Product product);
        void onEditClick(Product product);
        void onDeleteClick(Product product);
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
        holder.tvStockCount.setText(String.format(Locale.US, "Qty: %d", p.getQuantity()));
        holder.tvMinCaption.setText(String.format(Locale.US, "min. %d", p.getMinStockThreshold()));

        double lineVal = p.getUnitPrice() * p.getQuantity();
        holder.tvLineValue.setText(String.format(Locale.US, "Total: $%.2f", lineVal));

        // Asynchronous Picasso Image Loading
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

        // Progress bar calculation: quantity / (2 x minimum), capped at 100%
        int minThresh = p.getMinStockThreshold() > 0 ? p.getMinStockThreshold() : 1;
        int maxProg = 2 * minThresh;
        int progressPercent = (int) Math.min(100, Math.round(((double) p.getQuantity() / maxProg) * 100));

        // Animation over 250ms respecting reduced motion
        boolean animationsEnabled = Settings.Global.getFloat(
                context.getContentResolver(), 
                Settings.Global.ANIMATOR_DURATION_SCALE, 1f) != 0f;

        if (animationsEnabled) {
            ObjectAnimator anim = ObjectAnimator.ofInt(holder.progressStock, "progress", holder.progressStock.getProgress(), progressPercent);
            anim.setDuration(250);
            anim.start();
        } else {
            holder.progressStock.setProgress(progressPercent);
        }

        // Low stock vs in stock styling
        boolean isLow = p.isLowStock();
        if (isLow) {
            holder.tvStockStatusBadge.setVisibility(View.VISIBLE);
            holder.tvStockStatusBadge.setText("Low stock");
            holder.tvStockStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.stock_low));
            holder.tvStockStatusBadge.setBackgroundResource(R.drawable.badge_low_stock);

            holder.viewLowStockStrip.setVisibility(View.VISIBLE);
            holder.cardProduct.setCardBackgroundColor(ContextCompat.getColor(context, R.color.row_low_bg));
            holder.cardProduct.setStrokeColor(ContextCompat.getColor(context, R.color.stock_low));
            holder.cardProduct.setStrokeWidth(1);
            holder.progressStock.setProgressTintList(ContextCompat.getColorStateList(context, R.color.stock_low));
        } else {
            holder.tvStockStatusBadge.setVisibility(View.VISIBLE);
            holder.tvStockStatusBadge.setText("In stock");
            holder.tvStockStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.stock_ok));
            holder.tvStockStatusBadge.setBackgroundResource(R.drawable.badge_in_stock);

            holder.viewLowStockStrip.setVisibility(View.GONE);
            holder.cardProduct.setCardBackgroundColor(Color.WHITE);
            holder.cardProduct.setStrokeColor(ContextCompat.getColor(context, R.color.card_border));
            holder.cardProduct.setStrokeWidth(1);
            holder.progressStock.setProgressTintList(ContextCompat.getColorStateList(context, R.color.primary_charcoal));
        }

        // Click listeners
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) listener.onProductClick(p);
        });

        holder.btnPlus.setOnClickListener(v -> {
            if (listener != null) listener.onPlusClick(p);
        });

        holder.btnEdit.setOnClickListener(v -> {
            if (listener != null) listener.onEditClick(p);
        });

        holder.btnDelete.setOnClickListener(v -> {
            if (listener != null) listener.onDeleteClick(p);
        });
    }

    @Override
    public int getItemCount() {
        return productList.size();
    }

    static class ProductViewHolder extends RecyclerView.ViewHolder {
        final MaterialCardView cardProduct;
        final View viewLowStockStrip;
        final ImageView imgProductThumb;
        final TextView tvProductName;
        final TextView tvProductMeta;
        final TextView tvProductPrice;
        final TextView tvStockCount;
        final TextView tvStockStatusBadge;
        final ImageButton btnPlus;
        final ProgressBar progressStock;
        final TextView tvMinCaption;
        final TextView tvLineValue;
        final ImageButton btnEdit;
        final ImageButton btnDelete;

        public ProductViewHolder(@NonNull View itemView) {
            super(itemView);
            cardProduct = itemView.findViewById(R.id.cardProduct);
            viewLowStockStrip = itemView.findViewById(R.id.viewLowStockStrip);
            imgProductThumb = itemView.findViewById(R.id.imgProductThumb);
            tvProductName = itemView.findViewById(R.id.tvProductName);
            tvProductMeta = itemView.findViewById(R.id.tvProductMeta);
            tvProductPrice = itemView.findViewById(R.id.tvProductPrice);
            tvStockCount = itemView.findViewById(R.id.tvStockCount);
            tvStockStatusBadge = itemView.findViewById(R.id.tvStockStatusBadge);
            btnPlus = itemView.findViewById(R.id.btnPlus);
            progressStock = itemView.findViewById(R.id.progressStock);
            tvMinCaption = itemView.findViewById(R.id.tvMinCaption);
            tvLineValue = itemView.findViewById(R.id.tvLineValue);
            btnEdit = itemView.findViewById(R.id.btnEdit);
            btnDelete = itemView.findViewById(R.id.btnDelete);
        }
    }
}
