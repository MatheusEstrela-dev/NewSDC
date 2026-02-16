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

# 4. Background task to connect the app
(
    sleep 10 # Waiting for artisan serve to be ready
    echo -e "${GREEN}Sending deep link to device...${NC}"
    
    # Try nativephp scheme
    adb shell am start -a android.intent.action.VIEW -d "nativephp://${HOST_IP}:3001" > /dev/null 2>&1
    
    # Fallback/Alternatives if needed (usually nativephp:// is enough for the client app)
    # The client app listens for this scheme.
) &

# 5. Start NativePHP Jump Server
echo -e "${GREEN}Starting NativePHP Jump Server...${NC}"
# usage: native:jump --platform=android --ip=... --no-interaction
php artisan native:jump --platform=android --ip="$HOST_IP" --no-interaction
