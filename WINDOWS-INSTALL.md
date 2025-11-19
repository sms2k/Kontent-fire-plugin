# Windows Installation Guide - Fixing "Path Too Long" Error

## The Problem

Windows has a maximum path length of 260 characters. When you download the repository, the nested folder structure can exceed this limit, causing extraction errors like:

```
Error 0x80010135: Path too long
```

## Solution Methods

### Method 1: Extract to Root Directory (EASIEST)

1. **Download** the `kontent-fire-plugin-v1.zip` file from the repository
2. **Extract to C:\** or another short path:
   - Right-click the zip file
   - Choose "Extract All"
   - Change the destination to: `C:\wp-plugins\`
   - Click Extract

3. **The extracted folder will be:** `C:\wp-plugins\kontent-fire\`

4. **Zip just the plugin folder:**
   - Go to `C:\wp-plugins\`
   - Right-click on the `kontent-fire` folder
   - Send to → Compressed (zipped) folder
   - Rename to `kontent-fire.zip`

5. **Upload to WordPress:**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose your `kontent-fire.zip` file
   - Click "Install Now"
   - Activate the plugin

### Method 2: Enable Long Paths in Windows 10/11

Windows 10 (version 1607+) and Windows 11 support long paths if you enable them:

1. **Open Registry Editor:**
   - Press `Win + R`
   - Type `regedit` and press Enter

2. **Navigate to:**
   ```
   HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Control\FileSystem
   ```

3. **Modify LongPathsEnabled:**
   - Double-click on `LongPathsEnabled`
   - Change value to `1`
   - Click OK

4. **Restart your computer**

5. **Now extract normally** to any location

### Method 3: Use 7-Zip (Alternative Tool)

7-Zip handles long paths better than Windows Explorer:

1. **Download 7-Zip:** https://www.7-zip.org/
2. **Install 7-Zip**
3. **Right-click the zip file**
4. **7-Zip → Extract to "kontent-fire\"**
5. **Extract to a short path** like `C:\temp\`

### Method 4: Direct Installation from Repository

If you cloned the git repository:

1. **Navigate to the plugin folder:**
   ```
   C:\path\to\repo\kontent-fire-plugin\
   ```

2. **Create zip using Windows PowerShell:**
   ```powershell
   Compress-Archive -Path "C:\path\to\repo\kontent-fire-plugin" -DestinationPath "C:\kontent-fire.zip"
   ```

3. **Upload to WordPress** as usual

### Method 5: Use Shorter Extraction Path

Instead of extracting to:
```
C:\Users\YourName\Downloads\kontent-fire-plugin\kontent-fire-plugin\...
```

Extract directly to:
```
C:\kf\
```

Then zip the contents and upload.

## Recommended Approach

**Best Method:**

1. Create folder: `C:\wp\`
2. Download the code from the repository
3. Extract directly to: `C:\wp\kontent-fire\`
4. Zip the `kontent-fire` folder
5. Upload to WordPress

This keeps paths short and avoids the 260 character limit.

## After Installation

Once installed in WordPress:

1. **Activate the plugin**
2. **Go to:** Kontent Fire → Settings
3. **Add your API keys:**
   - Claude API Key (Anthropic)
   - OpenAI API Key
   - Gemini API Key
   - Google Cloud Project ID (for Imagen 4)

4. **Connect social media accounts:** Kontent Fire → Platforms

5. **Start using:** Kontent Fire → Auto-Blog

## Troubleshooting

### Still Getting Path Too Long Error?

**Option A: Use the pre-built zip**
- Download `kontent-fire-plugin-v1.zip` from the releases
- This has a shorter folder structure

**Option B: Rename folders**
- Rename `kontent-fire-plugin` to just `kf`
- All paths will be significantly shorter

**Option C: Use WSL (Windows Subsystem for Linux)**
- If you have WSL installed, extract there
- Linux doesn't have the 260 character limit
- Then copy to Windows once extracted

### Verification

After extraction, you should see:
```
kontent-fire/
├── kontent-fire.php (main plugin file)
├── includes/
│   ├── api/
│   ├── content-generation/
│   ├── seo/
│   └── ...
├── admin/
└── public/
```

If these folders and files exist, you're ready to zip and upload!

## Notes

- The plugin folder MUST be named `kontent-fire` or `kontent-fire-plugin`
- WordPress requires the main PHP file to be in the root of the zip
- Do NOT zip the parent folder; zip the plugin folder itself

## Need Help?

If you continue to experience issues:
1. Try Method 1 (extract to C:\)
2. Use 7-Zip instead of Windows Explorer
3. Enable long paths in Windows (Method 2)

The plugin will work once properly extracted and zipped!
