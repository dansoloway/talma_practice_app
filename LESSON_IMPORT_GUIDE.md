# WeSpeak Lesson Import Guide

## 🎯 **Purpose**
Import lessons and vocabulary from CSV files into the WeSpeak database.

## 📁 **Required Files**
Place these CSV files in the project root:
- `we speak vocab - sessions.csv` - Lesson information
- `we speak vocab - vocab.csv` - Vocabulary words

## 📊 **CSV Format**

### **Sessions CSV Format:**
```csv
id,grade_level,session_title,title
1,7th Grade,Session 3,Making a Volcano
2,7th Grade,Session 4 - Part A,The Scientific Method
3,7th Grade,Session 4 - Part B,Making a paper airplane
```

### **Vocabulary CSV Format:**
```csv
session_id,word
1,science
1,experiment
1,volcano
2,ice
2,melts
```

## 🚀 **Usage**

### **Local Testing:**
```bash
# Test import (will ask for confirmation if lessons exist)
php artisan wespeak:import-lessons

# Force import (skip confirmation)
php artisan wespeak:import-lessons --force
```

### **Production Deployment:**
```bash
# 1. Upload CSV files to server root
scp "we speak vocab - sessions.csv" server:/path/to/wespeak/
scp "we speak vocab - vocab.csv" server:/path/to/wespeak/

# 2. SSH into production server
ssh server

# 3. Navigate to project
cd /path/to/wespeak/

# 4. Run import
php artisan wespeak:import-lessons
```

## ✅ **What Gets Created**

### **Lessons:**
- **Title** - From sessions CSV
- **Slug** - Auto-generated from title
- **Grade Level** - Extracted from grade_level column (e.g., "7th Grade" → "7")
- **Session Number** - Extracted from session_title (e.g., "Session 3" → 3)
- **Session Title** - Full session title from CSV
- **Instructions** - Default instruction text
- **Active** - Set to true
- **Sort Order** - Based on session ID

### **Vocabulary:**
- **English Word** - From vocabulary CSV
- **Lesson ID** - Linked to created lesson
- **Sort Order** - Order within lesson
- **Active** - Set to true

## 🔧 **Features**

### **Duplicate Prevention:**
- Skips lessons that already exist (based on slug)
- Shows warning for existing lessons
- Continues with remaining lessons

### **Error Handling:**
- Validates CSV files exist
- Shows clear error messages
- Provides import summary

### **Smart Parsing:**
- Extracts grade numbers from "7th Grade", "8th Grade"
- Extracts session numbers from "Session 3", "Session 4 - Part A"
- Generates unique slugs from lesson titles

## 📊 **Example Output**
```
🚀 WeSpeak Lesson Import Tool
================================
📁 Found CSV files:
  ✓ /path/to/we speak vocab - sessions.csv
  ✓ /path/to/we speak vocab - vocab.csv

Creating WeSpeak lessons and vocabulary...
Creating lesson: Making a Volcano
  ⚠️  Lesson already exists: Making a Volcano (ID: 8)
Creating lesson: The Scientific Method
  Adding 10 vocabulary words
  ✓ Created lesson: The Scientific Method (ID: 10)

🎉 Import completed successfully!
📊 Database Summary:
  • Total Lessons: 13
  • Total Vocabulary Words: 65
```

## 🚨 **Troubleshooting**

### **CSV File Not Found:**
```
❌ Sessions CSV file not found: /path/to/we speak vocab - sessions.csv
```
**Solution:** Make sure CSV files are in the project root directory.

### **Database Connection Error:**
**Solution:** Check database credentials in `.env` file.

### **Duplicate Lesson Error:**
**Solution:** Script automatically skips duplicates and shows warning.

## 🎯 **Next Steps After Import**
1. **Check lessons** in admin panel
2. **Add images** to vocabulary words
3. **Create prompts** for sentence completion
4. **Set up matching games** and flashcards
5. **Test student experience**

---

## 🔄 **Re-running the Import**
- Safe to run multiple times
- Skips existing lessons automatically
- Only creates new lessons that don't exist
- Use `--force` flag to skip confirmation prompts
