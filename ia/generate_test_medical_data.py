#!/usr/bin/env python3
"""
Script para generar datos de prueba de casos médicos
Simula emails de Gmail procesados y los transforma en casos médicos para testing
"""

import os
import json
import sys
from datetime import datetime, timedelta
import random

# Agregar el directorio Functions al path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from gmail_to_medical_transformer import GmailToMedicalTransformer

def generate_test_email_data():
    """
    Genera datos de emails de prueba que simulan emails médicos procesados
    """
    
    # Plantillas de emails médicos
    medical_email_templates = [
        {
            "subject": "Referencia urgente - Dolor torácico agudo",
            "sender": "dr.mendoza@hospitalsanjose.com",
            "sender_name": "Dr. Carlos Mendoza",
            "content": """
            Estimado colega,
            
            Le refiero paciente María Elena Rodríguez, femenina de 45 años, quien presenta:
            
            MOTIVO DE CONSULTA:
            Dolor torácico de inicio súbito, tipo opresivo, irradiado a brazo izquierdo
            
            ANTECEDENTES:
            - Hipertensión arterial diagnosticada hace 2 años
            - Diabetes mellitus tipo 2 en tratamiento
            - Madre con antecedente de infarto agudo de miocardio
            
            SIGNOS VITALES:
            - Presión arterial: 150/95 mmHg
            - Frecuencia cardíaca: 110 bpm
            - Temperatura: 36.8°C
            - Saturación de oxígeno: 96%
            
            MEDICAMENTOS ACTUALES:
            - Losartán 50mg cada 24 horas
            - Metformina 850mg cada 12 horas
            - Atorvastatina 20mg cada 24 horas
            
            Requiere evaluación cardiológica urgente.
            
            Cordialmente,
            Dr. Carlos Mendoza
            Medicina Interna
            Hospital San José
            """,
            "priority": "urgente",
            "specialty": "cardiología"
        },
        {
            "subject": "Consulta cardiológica - Insuficiencia cardíaca",
            "sender": "dra.garcia@clinicacentral.com",
            "sender_name": "Dra. Ana García",
            "content": """
            Estimado especialista,
            
            Paciente: Carlos Alberto Pérez
            Edad: 62 años
            Género: Masculino
            
            DIAGNÓSTICO:
            Insuficiencia cardíaca descompensada
            
            HISTORIA CLÍNICA:
            Paciente con antecedente de infarto agudo de miocardio previo, presenta disnea de esfuerzo progresiva y edema en miembros inferiores.
            
            SIGNOS VITALES:
            - Presión arterial: 140/85 mmHg
            - Frecuencia cardíaca: 95 bpm
            - Saturación de oxígeno: 92%
            - Frecuencia respiratoria: 20 rpm
            
            ANTECEDENTES:
            - Infarto agudo de miocardio previo
            - Hipertensión arterial
            - Dislipidemia
            
            Solicito evaluación para ajuste de tratamiento.
            
            Dra. Ana García
            Medicina Interna
            """,
            "priority": "media",
            "specialty": "cardiología"
        },
        {
            "subject": "Embarazo de alto riesgo - Evaluación ginecológica",
            "sender": "dr.lopez@centrosalud.com",
            "sender_name": "Dr. Roberto López",
            "content": """
            Estimada colega,
            
            Paciente: Ana Sofía Martínez
            Edad: 28 años
            Género: Femenino
            
            DIAGNÓSTICO:
            Embarazo de alto riesgo - 32 semanas de gestación
            
            MOTIVO DE REFERENCIA:
            Hipertensión gestacional con proteinuria
            
            ANTECEDENTES OBSTÉTRICOS:
            - Primigesta
            - Embarazo actual sin complicaciones hasta semana 30
            
            SIGNOS VITALES:
            - Presión arterial: 160/100 mmHg
            - Frecuencia cardíaca: 88 bpm
            - Temperatura: 36.6°C
            
            LABORATORIOS:
            - Proteinuria: 2+ en orina
            - Plaquetas: 120,000
            
            Requiere evaluación urgente por ginecología.
            
            Dr. Roberto López
            Medicina General
            """,
            "priority": "urgente",
            "specialty": "ginecología"
        },
        {
            "subject": "Fractura de cadera - Evaluación ortopédica",
            "sender": "dra.torres@hospitalgeneral.com",
            "sender_name": "Dra. Carmen Torres",
            "content": """
            Estimado ortopedista,
            
            Paciente: Roberto Jiménez
            Edad: 55 años
            Género: Masculino
            
            DIAGNÓSTICO:
            Fractura de cadera derecha
            
            MECANISMO DE TRAUMA:
            Caída desde altura de aproximadamente 2 metros
            
            EXAMEN FÍSICO:
            - Dolor intenso en cadera derecha
            - Limitación funcional completa
            - Deformidad evidente
            
            ESTUDIOS REALIZADOS:
            - Radiografía de cadera: Fractura de cuello femoral
            
            Requiere evaluación ortopédica para manejo quirúrgico.
            
            Dra. Carmen Torres
            Urgencias
            """,
            "priority": "media",
            "specialty": "ortopedia"
        },
        {
            "subject": "Accidente cerebrovascular - Evaluación neurológica urgente",
            "sender": "dr.ramirez@hospitalregional.com",
            "sender_name": "Dr. Luis Ramírez",
            "content": """
            Estimado neurólogo,
            
            Paciente: José Miguel Torres
            Edad: 55 años
            Género: Masculino
            
            DIAGNÓSTICO PRESUNTIVO:
            Accidente cerebrovascular agudo
            
            CUADRO CLÍNICO:
            - Inicio súbito de hemiparesia izquierda
            - Disartria
            - Desviación de comisura bucal
            - Tiempo de evolución: 2 horas
            
            SIGNOS VITALES:
            - Presión arterial: 180/110 mmHg
            - Frecuencia cardíaca: 92 bpm
            - Glicemia: 140 mg/dl
            
            ANTECEDENTES:
            - Hipertensión arterial no controlada
            - Tabaquismo
            
            URGENTE: Requiere evaluación neurológica inmediata para trombolisis.
            
            Dr. Luis Ramírez
            Urgencias
            """,
            "priority": "urgente",
            "specialty": "neurología"
        }
    ]
    
    # Generar emails de prueba
    test_emails = []
    
    for i, template in enumerate(medical_email_templates):
        # Crear timestamp aleatorio en los últimos 7 días
        days_ago = random.randint(0, 7)
        hours_ago = random.randint(0, 23)
        minutes_ago = random.randint(0, 59)
        
        email_date = datetime.now() - timedelta(days=days_ago, hours=hours_ago, minutes=minutes_ago)
        
        # Crear estructura de email profesional
        email_data = {
            "document_identification": {
                "unique_identifier": f"test_email_{i+1:03d}_{int(email_date.timestamp())}",
                "document_type": "email_communication",
                "schema_version": "2.0.0",
                "processing_timestamp": datetime.now().isoformat(),
                "processing_system": "Gmail Data Extraction System v2.0 - Test Mode",
                "data_integrity_status": "verified"
            },
            "communication_metadata": {
                "message_identification": {
                    "message_id": f"<test_{i+1}@example.com>",
                    "thread_id": f"thread_{i+1}",
                    "conversation_topic": template["subject"],
                    "in_reply_to_message": "",
                    "reference_messages": []
                },
                "temporal_information": {
                    "sent_datetime": email_date.isoformat(),
                    "sent_datetime_string": email_date.strftime("%a, %d %b %Y %H:%M:%S %z"),
                    "received_datetime": email_date.isoformat(),
                    "timezone_information": "+0000",
                    "processing_datetime": datetime.now().isoformat()
                },
                "participant_information": {
                    "sender_details": [
                        {
                            "display_name": template["sender_name"],
                            "email_address": template["sender"],
                            "address_type": "primary",
                            "validation_status": "valid"
                        }
                    ],
                    "primary_recipients": [
                        {
                            "display_name": "Sistema de Referencia",
                            "email_address": "referencia@hospital.com",
                            "address_type": "primary",
                            "validation_status": "valid"
                        }
                    ],
                    "carbon_copy_recipients": [],
                    "blind_carbon_copy_recipients": [],
                    "reply_to_addresses": [],
                    "total_recipient_count": 1
                },
                "priority_and_sensitivity": {
                    "priority_level": template["priority"],
                    "importance_level": "high" if template["priority"] == "urgente" else "normal",
                    "sensitivity_classification": "normal",
                    "delivery_receipt_requested": False,
                    "read_receipt_requested": False
                }
            },
            "content_analysis": {
                "subject_information": {
                    "subject_line": template["subject"],
                    "subject_length": len(template["subject"]),
                    "subject_language": "spanish",
                    "contains_special_characters": True
                },
                "content_structure": {
                    "is_multipart_message": False,
                    "content_type": "text/plain",
                    "character_encoding": "utf-8",
                    "message_size_bytes": len(template["content"]),
                    "message_size_human_readable": f"{len(template['content']) / 1024:.1f} KB"
                },
                "body_content": {
                    "plain_text_content": template["content"],
                    "html_content": "",
                    "raw_content": template["content"],
                    "content_preview": template["content"][:200] + "...",
                    "estimated_reading_time_minutes": max(1, len(template["content"].split()) // 200),
                    "word_count": len(template["content"].split()),
                    "character_count": len(template["content"]),
                    "contains_html_formatting": False,
                    "detected_language": "spanish"
                }
            },
            "attachment_information": {
                "attachment_summary": {
                    "has_attachments": False,
                    "total_attachment_count": 0,
                    "total_attachment_size_bytes": 0,
                    "total_attachment_size_human_readable": "0 B"
                },
                "attachment_details": [],
                "attachment_categories": {
                    "document_files": [],
                    "image_files": [],
                    "archive_files": [],
                    "executable_files": [],
                    "other_files": [],
                    "category_counts": {
                        "documents": 0,
                        "images": 0,
                        "archives": 0,
                        "executables": 0,
                        "other": 0
                    }
                },
                "security_assessment": {
                    "potentially_dangerous_files": [],
                    "overall_risk_level": "none",
                    "scan_timestamp": datetime.now().isoformat()
                }
            },
            "source_type": "professional",
            "source_path": f"/test/email_{i+1}"
        }
        
        test_emails.append(email_data)
    
    return test_emails

def main():
    """
    Función principal para generar datos de prueba
    """
    print("=" * 80)
    print("GENERADOR DE DATOS DE PRUEBA - CASOS MÉDICOS")
    print("=" * 80)
    print("Generando emails de prueba y transformándolos en casos médicos...")
    print()
    
    try:
        # Generar emails de prueba
        print("📧 Generando emails de prueba...")
        test_emails = generate_test_email_data()
        print(f"✅ Generados {len(test_emails)} emails de prueba")
        
        # Crear directorio de prueba
        base_path = os.path.dirname(__file__)
        test_dir = os.path.join(base_path, "test_data")
        os.makedirs(test_dir, exist_ok=True)
        
        # Guardar emails de prueba en formato profesional
        professional_dir = os.path.join(base_path, "Professional_Email_Records")
        os.makedirs(professional_dir, exist_ok=True)
        
        for email in test_emails:
            unique_id = email["document_identification"]["unique_identifier"]
            email_dir = os.path.join(professional_dir, unique_id)
            os.makedirs(email_dir, exist_ok=True)
            
            # Guardar email completo
            with open(os.path.join(email_dir, "comprehensive_email_record.json"), 'w', encoding='utf-8') as f:
                json.dump(email, f, indent=2, ensure_ascii=False)
        
        print(f"💾 Emails guardados en: {professional_dir}")
        
        # Transformar emails en casos médicos
        print("🏥 Transformando emails en casos médicos...")
        
        transformer = GmailToMedicalTransformer(base_path)
        medical_cases = transformer.transform_all_medical_emails()
        
        print(f"✅ Se generaron {len(medical_cases)} casos médicos")
        
        # Guardar casos médicos para el frontend
        output_path = transformer.save_medical_cases_json(medical_cases)
        
        if output_path:
            print(f"💾 Casos médicos guardados en: {output_path}")
            print()
            print("📋 RESUMEN DE CASOS MÉDICOS GENERADOS:")
            print("-" * 50)
            
            # Mostrar estadísticas
            priorities = {}
            specialties = {}
            
            for case in medical_cases:
                priority = case['priority']
                specialty = case['specialty']
                
                priorities[priority] = priorities.get(priority, 0) + 1
                specialties[specialty] = specialties.get(specialty, 0) + 1
            
            print("Por Prioridad:")
            for priority, count in priorities.items():
                print(f"  {priority}: {count} casos")
            
            print("\nPor Especialidad:")
            for specialty, count in specialties.items():
                print(f"  {specialty}: {count} casos")
            
            print()
            print("🎉 DATOS DE PRUEBA GENERADOS EXITOSAMENTE")
            print("Los datos están listos para ser consumidos por el frontend")
            print(f"Archivo de datos: {output_path}")
            print()
            print("Para probar en el frontend:")
            print("1. Inicia el servidor Next.js: npm run dev")
            print("2. Ve a la página de casos médicos")
            print("3. Los datos se cargarán automáticamente desde la API")
            
        else:
            print("❌ Error guardando casos médicos")
            return 1
        
    except Exception as e:
        print(f"❌ Error generando datos de prueba: {str(e)}")
        return 1
    
    return 0

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
