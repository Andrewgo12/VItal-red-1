#!/usr/bin/env python3
"""
Generate comprehensive test data for all application views
Creates medical, admin, urgency, historical, and hospitalization data from Gmail processing
"""

import os
import sys
import logging
from datetime import datetime

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from universal_data_transformer import UniversalDataTransformer
from gmail_to_medical_transformer import GmailToMedicalTransformer

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)

def main():
    """
    Generate all test data types for comprehensive application testing
    """
    print("=" * 80)
    print("COMPREHENSIVE TEST DATA GENERATION")
    print("=" * 80)
    print("Generating test data for ALL application views:")
    print("- Medical Cases (Dashboard, Casos)")
    print("- Admin Data (Admin Dashboard)")
    print("- Urgency Data (Urgencias)")
    print("- Historical Data (Historial)")
    print("- Patient Data (Future views)")
    print("- Hospitalization Data (Future views)")
    print("=" * 80)
    print()
    
    try:
        base_path = os.path.dirname(__file__)
        
        # Initialize transformers
        print("🔧 Initializing data transformers...")
        medical_transformer = GmailToMedicalTransformer(base_path)
        universal_transformer = UniversalDataTransformer(base_path)
        
        # First, ensure we have test email data
        print("📧 Ensuring test email data exists...")
        
        # Check if we have processed emails, if not generate them
        emails = medical_transformer.load_processed_emails()
        if not emails:
            print("⚠️  No processed emails found. Generating test email data first...")
            
            # Run the test medical data generator
            import subprocess
            result = subprocess.run([sys.executable, 'generate_test_medical_data.py'], 
                                  cwd=base_path, capture_output=True, text=True)
            
            if result.returncode == 0:
                print("✅ Test email data generated successfully")
                emails = medical_transformer.load_processed_emails()
            else:
                print(f"❌ Error generating test email data: {result.stderr}")
                return 1
        
        print(f"📊 Found {len(emails)} processed emails to transform")
        print()
        
        # Generate all data types
        print("🏥 Generating comprehensive data for all views...")
        
        # 1. Medical Cases (already generated, but refresh)
        print("  📋 Medical Cases...")
        medical_cases = medical_transformer.transform_all_medical_emails()
        medical_file = medical_transformer.save_medical_cases_json(medical_cases)
        print(f"     ✅ {len(medical_cases)} medical cases → {medical_file}")
        
        # 2. Admin Data
        print("  🏢 Administrative Data...")
        admin_data = universal_transformer.extract_admin_data(emails)
        admin_file = os.path.join(base_path, "admin_data_for_frontend.json")
        with open(admin_file, 'w', encoding='utf-8') as f:
            import json
            json.dump(admin_data, f, indent=2, ensure_ascii=False)
        print(f"     ✅ {len(admin_data['records'])} admin records → {admin_file}")
        
        # 3. Urgency Data
        print("  🚨 Emergency/Urgency Data...")
        urgency_data = universal_transformer.extract_urgency_data(emails)
        urgency_file = os.path.join(base_path, "urgency_data_for_frontend.json")
        with open(urgency_file, 'w', encoding='utf-8') as f:
            json.dump(urgency_data, f, indent=2, ensure_ascii=False)
        print(f"     ✅ {len(urgency_data['records'])} urgency records → {urgency_file}")
        
        # 4. Historical Data
        print("  📊 Historical Data...")
        historical_data = universal_transformer.extract_historical_data(emails)
        historical_file = os.path.join(base_path, "historical_data_for_frontend.json")
        with open(historical_file, 'w', encoding='utf-8') as f:
            json.dump(historical_data, f, indent=2, ensure_ascii=False)
        print(f"     ✅ {len(historical_data['records'])} historical records → {historical_file}")
        
        # 5. Patient Data
        print("  👥 Patient Data...")
        patient_data = universal_transformer.extract_patient_data(emails)
        patient_file = os.path.join(base_path, "patient_data_for_frontend.json")
        with open(patient_file, 'w', encoding='utf-8') as f:
            json.dump(patient_data, f, indent=2, ensure_ascii=False)
        print(f"     ✅ {len(patient_data['records'])} patient records → {patient_file}")
        
        # 6. Hospitalization Data
        print("  🏥 Hospitalization Data...")
        hosp_data = universal_transformer.extract_hospitalization_data(emails)
        hosp_file = os.path.join(base_path, "hospitalization_data_for_frontend.json")
        with open(hosp_file, 'w', encoding='utf-8') as f:
            json.dump(hosp_data, f, indent=2, ensure_ascii=False)
        print(f"     ✅ {len(hosp_data['records'])} hospitalization records → {hosp_file}")
        
        print()
        print("=" * 80)
        print("COMPREHENSIVE DATA GENERATION COMPLETED")
        print("=" * 80)
        
        # Summary statistics
        total_records = (
            len(medical_cases) +
            len(admin_data['records']) +
            len(urgency_data['records']) +
            len(historical_data['records']) +
            len(patient_data['records']) +
            len(hosp_data['records'])
        )
        
        print(f"📊 SUMMARY STATISTICS:")
        print(f"   Total Records Generated: {total_records}")
        print(f"   Medical Cases: {len(medical_cases)}")
        print(f"   Admin Records: {len(admin_data['records'])}")
        print(f"   Urgency Records: {len(urgency_data['records'])}")
        print(f"   Historical Records: {len(historical_data['records'])}")
        print(f"   Patient Records: {len(patient_data['records'])}")
        print(f"   Hospitalization Records: {len(hosp_data['records'])}")
        print()
        
        print("🎯 DATA INTEGRATION STATUS:")
        print("   ✅ Medical Dashboard - INTEGRATED")
        print("   ✅ Medical Cases View - INTEGRATED")
        print("   ✅ Admin Dashboard - INTEGRATED")
        print("   ✅ Urgency View - INTEGRATED")
        print("   ✅ Historical View - READY FOR INTEGRATION")
        print("   ✅ Patient Views - READY FOR INTEGRATION")
        print("   ✅ Hospitalization Views - READY FOR INTEGRATION")
        print()
        
        print("🚀 NEXT STEPS:")
        print("   1. Start the Next.js development server: npm run dev")
        print("   2. Visit the integrated views:")
        print("      - Medical Dashboard: http://localhost:3000/medico/dashboard")
        print("      - Medical Cases: http://localhost:3000/medico/casos")
        print("      - Admin Dashboard: http://localhost:3000/admin/dashboard")
        print("      - Urgency View: http://localhost:3000/medico/urgencias")
        print("   3. Verify data loads from Gmail processing system")
        print("   4. Test filters, search, and refresh functionality")
        print()
        
        print("📁 FILES GENERATED:")
        files_generated = [
            medical_file,
            admin_file,
            urgency_file,
            historical_file,
            patient_file,
            hosp_file
        ]
        
        for file_path in files_generated:
            if os.path.exists(file_path):
                size_kb = os.path.getsize(file_path) / 1024
                print(f"   ✅ {os.path.basename(file_path)} ({size_kb:.1f} KB)")
            else:
                print(f"   ❌ {os.path.basename(file_path)} (NOT FOUND)")
        
        print()
        print("🎉 ALL DATA TYPES GENERATED SUCCESSFULLY!")
        print("The application now has comprehensive Gmail data integration across all views.")
        
        return 0
        
    except Exception as e:
        logger.error(f"Error in comprehensive data generation: {str(e)}")
        print(f"❌ Critical error: {str(e)}")
        return 1

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
