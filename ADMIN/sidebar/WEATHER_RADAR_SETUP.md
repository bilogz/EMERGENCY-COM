# Weather Radar Map Setup Instructions

## Overview
The weather monitoring system now includes real-time precipitation radar, cloud cover, and temperature overlays - similar to [Zoom Earth](https://zoom.earth/places/philippines/manila/#map=radar).

## Features
- ☔ **Precipitation Radar** - Shows real-time rain and storm patterns
- ☁️ **Cloud Cover** - Displays cloud formations and movements  
- 🌡️ **Temperature Overlay** - Temperature distribution across the map
- 💨 **Wind Arrows** (Optional) - Wind direction indicators

## Setup Required

### 1. Get OpenWeather API Key (FREE)

You need an API key from OpenWeatherMap to display weather data:

1. Go to: https://openweathermap.org/api
2. Click **"Sign Up"** (Free account)
3. Verify your email
4. Go to **"API Keys"** in your account dashboard
5. Copy your API key

**Free Plan Includes:**
- 60 calls/minute
- 1,000,000 calls/month
- Current weather data
- Precipitation, clouds, temperature maps
- Perfect for this application!

### 2. Add API Key to Code

Open: `EMERGENCY-COM/ADMIN/sidebar/weather-monitoring.php`

Find line ~1790 (in the `initWeatherLayers()` function):

```javascript
const OPENWEATHER_API_KEY = 'YOUR_API_KEY_HERE';
```

Replace `YOUR_API_KEY_HERE` with your actual API key:

```javascript
const OPENWEATHER_API_KEY = 'abc123xyz456yourkeyhere';
```

### 3. Save and Test

1. Save the file
2. Refresh your browser
3. Click the **"Radar"** button to see precipitation
4. Click **"Clouds"** to see cloud cover
5. Click **"Temp"** to see temperature overlay

## Usage

### Map Controls (Top Right)

- **Radar** 🌧️ - Toggle precipitation/rain radar (shows typhoons, storms, rain intensity)
- **Clouds** ☁️ - Toggle cloud cover visualization
- **Temp** 🌡️ - Toggle temperature heat map
- **Wind** 💨 - Toggle wind direction arrows (optional)
- **Minimize** ⬇️ - Minimize the map
- **Fullscreen** ⛶ - Full screen mode

### Features

**Typhoon/Storm Detection:**
- Heavy precipitation shows as dark blue/red areas on radar
- Enable "Radar" layer to see storms in real-time
- Combine with "Clouds" to see storm formations

**Rain Intensity:**
- Light Blue = Light rain
- Blue = Moderate rain  
- Dark Blue = Heavy rain
- Red/Purple = Very heavy rain/storms

**Temperature:**
- Color overlay shows temperature distribution
- Helps predict weather patterns

## Troubleshooting

**No weather data showing?**
- Check your API key is correct
- Wait 10-15 minutes after signup (API activation)
- Check browser console for errors (F12)
- Verify internet connection

**Layers not loading?**
- Clear browser cache (Ctrl+Shift+R)
- Check OpenWeather API status: https://openweathermap.org/api
- Verify you haven't exceeded free tier limits

**Map looks like Zoom Earth now?**
- ✅ Yes! That's the goal
- Radar shows precipitation like Zoom Earth
- Clouds show atmospheric conditions
- Temperature shows heat distribution
- All real-time data from OpenWeatherMap

## Alternative: RainViewer API (No API Key Required!)

If you don't want to sign up for OpenWeather, you can use **RainViewer** which requires NO API key:

In the `initWeatherLayers()` function, replace the precipitation layer with:

```javascript
// Free RainViewer Radar (No API key needed!)
precipitationLayer = L.tileLayer(
    'https://tilecache.rainviewer.com/v2/radar/{time}/{size}/{z}/{x}/{y}/256/{color}.png',
    {
        attribution: 'Weather radar © RainViewer',
        opacity: 0.6,
        maxZoom: 19,
        time: Math.floor(Date.now() / 600000) * 600, // Updates every 10 mins
        size: 512,
        color: 2 // Color scheme 0-8
    }
);
```

RainViewer provides:
- Global radar coverage
- No signup needed
- Updates every 10 minutes
- Free forever

## Support

For issues or questions about:
- **API Key**: https://openweathermap.org/faq
- **Weather Data**: https://openweathermap.org/api
- **RainViewer**: https://www.rainviewer.com/api.html

---

**Created:** December 2024  
**Compatible with:** Leaflet 1.9.4, OpenWeatherMap API v2.5

