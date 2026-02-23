#!/bin/bash

# Configuration
export ANDROID_HOME=${ANDROID_HOME:-$HOME/Android/Sdk}
export PATH=$PATH:$ANDROID_HOME/platform-tools
EMULATOR_BIN="$ANDROID_HOME/emulator/emulator"

# colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}==> Starting NativePHP Mobile Dev Environment${NC}"

# 1. Check for ADB
if ! command -v adb &> /dev/null; then
    echo -e "${RED}Error: adb not found.${NC} Please check your Android SDK installation."
    exit 1
fi

# 2. Check for connected devices
DEVICES=$(adb devices | grep -w 'device')
if [ -z "$DEVICES" ]; then
    echo -e "${YELLOW}No connected device found.${NC}"
    echo "Checking for available emulators..."
    
    if [ -x "$EMULATOR_BIN" ]; then
        # List AVDs
        AVDS=$($EMULATOR_BIN -list-avds)
        if [ -z "$AVDS" ]; then
             echo -e "${RED}No AVDs found.${NC} Please create one in Android Studio."
             exit 1
        fi
        
        # Pick the first one
        FIRST_AVD=$(echo "$AVDS" | head -n 1)
        echo -e "${GREEN}Launching emulator: ${FIRST_AVD}${NC}"
        $EMULATOR_BIN @$FIRST_AVD &
        
        echo "Waiting for emulator to become ready..."
        adb wait-for-device
        
        # Wait a bit more for boot completion (basic check)
        echo "Waiting for boot completion..."
        while [ "$(adb shell getprop sys.boot_completed | tr -d '\r')" != "1" ]; do
            sleep 1
        done
        echo -e "${GREEN}Emulator ready!${NC}"
    else
        echo -e "${RED}Emulator binary not found at $EMULATOR_BIN${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}Device connected.${NC}"
fi

# 3. Detect Host IP
# getting the first non-loopback IP
HOST_IP=$(hostname -I | awk '{print $1}')
echo -e "${YELLOW}Detected Host IP: ${HOST_IP}${NC}"

# 4. Start Laravel dev server on port 8000 (required by Jump proxy)
LARAVEL_PORT=8000
LARAVEL_PID=""

if ss -tlnp 2>/dev/null | grep -q ":${LARAVEL_PORT} "; then
    echo -e "${YELLOW}Laravel already running on port ${LARAVEL_PORT}${NC}"
else
    echo -e "${GREEN}Starting Laravel dev server on port ${LARAVEL_PORT}...${NC}"
    php artisan serve --host=0.0.0.0 --port=${LARAVEL_PORT} &>/dev/null &
    LARAVEL_PID=$!
    sleep 2
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:${LARAVEL_PORT}/ 2>/dev/null)
    if echo "$HTTP_CODE" | grep -qE '^[23]'; then
        echo -e "${GREEN}Laravel dev server ready (PID: ${LARAVEL_PID})${NC}"
    else
        echo -e "${RED}Warning: Laravel dev server may not have started correctly (HTTP ${HTTP_CODE})${NC}"
    fi
fi

# Cleanup on exit
cleanup() {
    if [ -n "$LARAVEL_PID" ]; then
        echo -e "${YELLOW}Stopping Laravel dev server (PID: ${LARAVEL_PID})...${NC}"
        kill $LARAVEL_PID 2>/dev/null
    fi
}
trap cleanup EXIT INT TERM

# 5. Background task to connect the app
(
    sleep 10 # Waiting for artisan serve to be ready
    echo -e "${GREEN}Sending deep link to device...${NC}"
    
    # Try nativephp scheme
    adb shell am start -a android.intent.action.VIEW -d "nativephp://${HOST_IP}:3001" > /dev/null 2>&1
    
    # Fallback/Alternatives if needed (usually nativephp:// is enough for the client app)
    # The client app listens for this scheme.
) &

# 6. Start NativePHP Jump Server
echo -e "${GREEN}Starting NativePHP Jump Server...${NC}"
# usage: native:jump --platform=android --ip=... --no-interaction
php artisan native:jump --platform=android --ip="$HOST_IP" --no-interaction
