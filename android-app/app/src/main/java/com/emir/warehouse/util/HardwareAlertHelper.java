package com.emir.warehouse.util;

import android.Manifest;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.VibrationEffect;
import android.os.Vibrator;
import android.os.VibratorManager;
import android.util.Log;

import androidx.core.app.ActivityCompat;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

import com.emir.warehouse.MainActivity;
import com.emir.warehouse.R;
import com.emir.warehouse.model.Product;

public class HardwareAlertHelper {
    private static final String TAG = "HardwareAlertHelper";
    public static final String CHANNEL_ID = "warehouse_low_stock_channel";

    /**
     * [SPEAKER 4 - WORKFLOW 5: Hardware Feedback & Low-Stock Alerts]
     * Triggers both Hardware Vibration (500ms) and Local Android System Notification
     * when a product drops below its safe minimum threshold.
     */
    public static void triggerLowStockAlert(Context context, Product product) {
        if (context == null || product == null) return;

        // 1. Hardware Vibration for 500ms
        triggerVibration(context, 500);

        // 2. Local Android System Notification
        sendLowStockNotification(context, product);
    }

    /**
     * Vibrate device for specified duration in milliseconds.
     * Supports both modern VibratorManager (Android 12+) and legacy Vibrator.
     */
    public static void triggerVibration(Context context, long durationMs) {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                VibratorManager vibratorManager = (VibratorManager) context.getSystemService(Context.VIBRATOR_MANAGER_SERVICE);
                if (vibratorManager != null) {
                    Vibrator vibrator = vibratorManager.getDefaultVibrator();
                    vibrator.vibrate(VibrationEffect.createOneShot(durationMs, VibrationEffect.DEFAULT_AMPLITUDE));
                    return;
                }
            }
            
            Vibrator vibrator = (Vibrator) context.getSystemService(Context.VIBRATOR_SERVICE);
            if (vibrator != null && vibrator.hasVibrator()) {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    vibrator.vibrate(VibrationEffect.createOneShot(durationMs, VibrationEffect.DEFAULT_AMPLITUDE));
                } else {
                    vibrator.vibrate(durationMs);
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error triggering vibrator service", e);
        }
    }

    /**
     * Fires a local Android System Notification with exact title and message requested by specification:
     * Title: "Low Stock Warning"
     * Message: "[Product Name] is down to [X] units (Minimum: [Y]). Reorder required."
     */
    public static void sendLowStockNotification(Context context, Product product) {
        try {
            createNotificationChannel(context);

            String title = "Low Stock Warning";
            String message = product.getName() + " is down to " + product.getQuantity() 
                    + " units (Minimum: " + product.getMinStockThreshold() + "). Reorder required.";

            Intent intent = new Intent(context, MainActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            PendingIntent pendingIntent = PendingIntent.getActivity(
                    context,
                    product.getId(),
                    intent,
                    PendingIntent.FLAG_UPDATE_CURRENT | (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M ? PendingIntent.FLAG_IMMUTABLE : 0)
            );

            NotificationCompat.Builder builder = new NotificationCompat.Builder(context, CHANNEL_ID)
                    .setSmallIcon(R.drawable.ic_warning)
                    .setContentTitle(title)
                    .setContentText(message)
                    .setStyle(new NotificationCompat.BigTextStyle().bigText(message))
                    .setPriority(NotificationCompat.PRIORITY_HIGH)
                    .setAutoCancel(true)
                    .setContentIntent(pendingIntent);

            NotificationManagerCompat notificationManager = NotificationManagerCompat.from(context);
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                if (ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                    Log.w(TAG, "POST_NOTIFICATIONS permission not granted");
                    return;
                }
            }

            notificationManager.notify(product.getId(), builder.build());
        } catch (Exception e) {
            Log.e(TAG, "Error posting system notification", e);
        }
    }

    private static void createNotificationChannel(Context context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            CharSequence name = context.getString(R.string.low_stock_channel_name);
            String description = context.getString(R.string.low_stock_channel_desc);
            int importance = NotificationManager.IMPORTANCE_HIGH;
            NotificationChannel channel = new NotificationChannel(CHANNEL_ID, name, importance);
            channel.setDescription(description);
            channel.enableVibration(true);

            NotificationManager notificationManager = context.getSystemService(NotificationManager.class);
            if (notificationManager != null) {
                notificationManager.createNotificationChannel(channel);
            }
        }
    }
}
