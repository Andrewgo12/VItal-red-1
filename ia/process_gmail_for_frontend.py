#!/usr/bin/env python3
"""
Script para procesar emails de Gmail y generar datos para el frontend
Ejecuta el procesamiento completo y transforma los datos en casos médicos
"""

import os
import sys
import logging
import time
from datetime import datetime

# Agregar el directorio Functions al path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

# Importar módulos del sistema
from gmail_to_medical_transformer import GmailToMedicalTransformer
from config import load_complete_config
from gmail_connector import GmailConnector
from metadata_extractor import MetadataExtractor
from attachment_processor import AttachmentProcessor
from text_extractor import TextExtractor
from json_converter import JSONConverter
from batch_processor import BatchProcessor
from monitoring import PerformanceMonitor, ProcessingLogger
from backup_recovery import BackupManager
from data_validator import QualityAssurance

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('gmail_processing.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

def process_single_email(email_msg, unique_id, processors, logger):
    """
    Procesa un email individual y retorna los resultados
    """
    try:
        start_time = time.time()
        
        # Extraer metadata
        metadata = processors['metadata_extractor'].extract_metadata(email_msg, unique_id)
        
        # Procesar attachments
        attachments = processors['attachment_processor'].process_email_attachments(email_msg, unique_id)
        
        # Extraer texto del cuerpo
        body_content = processors['text_extractor'].extract_email_body(email_msg)
        
        # Extraer texto de attachments
        extracted_text_data = {
            'email_body': body_content,
            'attachments': []
        }
        
        for attachment in attachments:
            if attachment.get('saved_successfully'):
                extracted_text = processors['text_extractor'].extract_text_from_file(
                    attachment['file_path'], 
                    attachment['original_filename']
                )
                extracted_text_data['attachments'].append({
                    'filename': attachment['original_filename'],
                    'text': extracted_text
                })
        
        # Guardar texto extraído
        text_file_path = processors['text_extractor'].save_extracted_text(unique_id, extracted_text_data)
        
        # Crear registro profesional
        processing_stats = {
            'processing_time': time.time() - start_time,
            'extraction_method': 'imap_direct',
            'retry_count': 0,
            'memory_usage_mb': 0,
            'batch_number': 1
        }
        
        professional_record = processors['json_converter'].create_professional_email_record(
            unique_id,
            metadata,
            body_content,
            attachments,
            extracted_text_data,
            processing_stats
        )
        
        # Guardar registro profesional
        json_file_path = processors['json_converter'].save_professional_email_record(unique_id, professional_record)
        
        return {
            'unique_id': unique_id,
            'success': True,
            'processing_time': processing_stats['processing_time'],
            'attachment_count': len(attachments),
            'subject': metadata.get('subject', 'Sin asunto'),
            'sender': metadata.get('from', [{}])[0].get('email', 'Desconocido') if metadata.get('from') else 'Desconocido',
            'date': metadata.get('date', ''),
            'json_path': json_file_path,
            'text_path': text_file_path
        }
        
    except Exception as e:
        logger.error(f"Error processing email {unique_id}: {str(e)}")
        return {
            'unique_id': unique_id,
            'success': False,
            'error': str(e),
            'processing_time': 0,
            'attachment_count': 0
        }

def main():
    """
    Función principal que ejecuta todo el proceso
    """
    print("=" * 80)
    print("PROCESAMIENTO DE GMAIL PARA FRONTEND")
    print("=" * 80)
    print("Iniciando procesamiento completo de emails de Gmail...")
    print("Los datos se transformarán en casos médicos para las vistas del frontend")
    print("=" * 80)
    print()
    
    try:
        # Cargar configuración
        print("📋 Cargando configuración...")
        config = load_complete_config()
        base_path = config['base_path']
        
        # Inicializar componentes
        print("🔧 Inicializando componentes del sistema...")
        
        # Conectar a Gmail
        gmail_connector = GmailConnector(config)
        if not gmail_connector.connect():
            raise Exception("No se pudo conectar a Gmail")
        
        # Inicializar procesadores
        processors = {
            'metadata_extractor': MetadataExtractor,
            'attachment_processor': AttachmentProcessor(base_path),
            'text_extractor': TextExtractor(base_path),
            'json_converter': JSONConverter(base_path)
        }
        
        # Inicializar monitoreo
        performance_monitor = PerformanceMonitor()
        processing_logger = ProcessingLogger(base_path)
        
        performance_monitor.start_monitoring()
        
        print("✅ Componentes inicializados correctamente")
        print()
        
        # Buscar emails
        print("🔍 Buscando emails en Gmail...")
        email_ids = gmail_connector.search_emails(config.get('search_query', 'ALL'))
        
        if not email_ids:
            print("⚠️  No se encontraron emails para procesar")
            return
        
        total_emails = len(email_ids)
        max_emails = config.get('max_emails', 0)
        
        if max_emails > 0 and total_emails > max_emails:
            email_ids = email_ids[:max_emails]
            total_emails = max_emails
            print(f"📊 Limitando procesamiento a {max_emails} emails")
        
        print(f"📊 Encontrados {total_emails} emails para procesar")
        print()
        
        # Procesar emails con batch processor
        def progress_callback(progress_percent, stats):
            print(f"📊 Progreso: {progress_percent:.1f}% - "
                  f"Procesados: {stats['processed_emails']}/{stats['total_emails']} - "
                  f"Éxito: {(stats['successful_emails']/max(1,stats['processed_emails'])*100):.1f}%")
        
        batch_processor = BatchProcessor(
            base_path=base_path,
            batch_size=min(50, max(10, total_emails // 10)),
            max_workers=min(4, max(1, total_emails // 100)),
            memory_limit_mb=config.get('memory_limit_mb', 2048),
            progress_callback=progress_callback
        )
        
        print("🚀 Iniciando procesamiento por lotes...")
        print(f"   Tamaño de lote: {batch_processor.batch_size}")
        print(f"   Workers: {batch_processor.max_workers}")
        print()
        
        # Procesar emails
        processed_emails = batch_processor.process_email_batch(
            email_ids,
            gmail_connector,
            processors,
            process_single_email
        )
        
        # Obtener estadísticas
        batch_stats = batch_processor.get_statistics()
        successful_count = batch_stats['successful_emails']
        failed_count = batch_stats['failed_emails']
        
        print()
        print("=" * 80)
        print("PROCESAMIENTO COMPLETADO")
        print("=" * 80)
        print(f"Emails procesados: {batch_stats['processed_emails']}")
        print(f"Exitosos: {successful_count}")
        print(f"Fallidos: {failed_count}")
        print(f"Tiempo total: {batch_stats['processing_time']:.2f}s")
        print("=" * 80)
        print()
        
        # Transformar emails en casos médicos
        print("🏥 Transformando emails en casos médicos...")
        
        transformer = GmailToMedicalTransformer(base_path)
        medical_cases = transformer.transform_all_medical_emails()
        
        print(f"✅ Se identificaron {len(medical_cases)} casos médicos")
        
        # Guardar casos médicos para el frontend
        output_path = transformer.save_medical_cases_json(medical_cases)
        
        if output_path:
            print(f"💾 Casos médicos guardados en: {output_path}")
            print()
            print("📋 RESUMEN DE CASOS MÉDICOS:")
            print("-" * 50)
            
            # Mostrar estadísticas de casos médicos
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
            print("🎉 PROCESO COMPLETADO EXITOSAMENTE")
            print("Los datos están listos para ser consumidos por el frontend")
            print(f"Archivo de datos: {output_path}")
            
        else:
            print("❌ Error guardando casos médicos")
        
        # Detener monitoreo
        performance_monitor.stop_monitoring()
        
        # Cerrar conexión
        gmail_connector.disconnect()
        
    except Exception as e:
        logger.error(f"Error en el proceso principal: {str(e)}")
        print(f"❌ Error crítico: {str(e)}")
        return 1
    
    return 0

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
