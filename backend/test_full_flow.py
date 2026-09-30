#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test Script: End-to-End Upload -> Langflow -> DB
"""

import requests
import tempfile
import json
import os
import sys

# Set encoding untuk Windows
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

BACKEND_URL = 'http://127.0.0.1:8000/api'

# Create test file dengan extension .jpg (valid untuk validation)
test_content = b"Kalau tidak bayar hari ini, kami sebar data keluarga kamu."
test_file = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
test_file.write(test_content)
test_file.close()

print("=" * 70)
print("END-TO-END TEST: Upload → Langflow → DB")
print("=" * 70)
print()

try:
    print("[1] Creating test file...")
    print(f"    File: {os.path.basename(test_file.name)}")
    print(f"    Content: {test_content.decode()}")
    print()

    print("[2] Testing POST /api/evidence/upload...")
    
    with open(test_file.name, 'rb') as f:
        files = {
            'file': ('test_evidence.jpg', f, 'image/jpeg'),
        }
        data = {
            'user_id': f'test_user_{os.getpid()}'
        }
        
        response = requests.post(f'{BACKEND_URL}/evidence/upload', files=files, data=data)

    print(f"    Status Code: {response.status_code}")
    print(f"    Response Body (first 500 chars): {response.text[:500]}")
    
    if response.status_code != 201:
        print(f"    X Upload failed!")
        try:
            print(f"    Full Response:")
            print(json.dumps(response.json(), indent=4))
        except:
            print(response.text)
        raise Exception("Upload failed")
    
    print(f"    ✓ Upload successful (201)")
    print()

    response_data = response.json()
    
    print("[3] Response Data:")
    print("    " + "-" * 44)
    print(f"    Evidence ID:     {response_data.get('evidence_id')}")
    print(f"    Upload Status:   {response_data.get('upload_status')}")
    
    ai_process = response_data.get('ai_process', {})
    if ai_process:
        print()
        print("    AI Process Result:")
        print(f"      Status:        {ai_process.get('status')}")
        print(f"      Category:      {ai_process.get('category')}")
        print(f"      Severity:      {ai_process.get('severity')}")
        print(f"      Confidence:    {ai_process.get('confidence')}")
        print(f"      Entities:      {ai_process.get('entities_count', 0)} extracted")
        reason = ai_process.get('reason', '')[:80]
        print(f"      Reason:        {reason}...")
    
    print("    " + "-" * 44)
    print()

    # Test 4: Fetch analysis result
    evidence_id = response_data.get('evidence_id')
    if evidence_id:
        print(f"[4] Testing GET /api/analysis/by-evidence/{evidence_id}...")
        
        analysis_response = requests.get(f"{BACKEND_URL}/analysis/by-evidence/{evidence_id}")
        
        if analysis_response.status_code == 200:
            print(f"    ✓ Analysis fetched successfully")
            print()
            
            analysis_data = analysis_response.json()
            print("    Analysis Data:")
            print(f"      Category:           {analysis_data.get('category')}")
            print(f"      Severity:           {analysis_data.get('severity')}")
            print(f"      Confidence:         {analysis_data.get('confidence')}")
            print(f"      Regulation Ref:     {json.dumps(analysis_data.get('regulation_reference', []))}")
            print()
        else:
            print(f"    ✗ Analysis fetch failed: {analysis_response.status_code}")
            print()

    print("=" * 70)
    print("✓ TEST PASSED")
    print("=" * 70)

except Exception as e:
    print()
    print("=" * 70)
    print("✗ TEST FAILED")
    print(f"Error: {str(e)}")
    print("=" * 70)
    exit(1)

finally:
    os.unlink(test_file.name)
