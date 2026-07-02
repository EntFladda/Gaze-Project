#!/usr/bin/env python3
"""
Training Monitor Script
Menjalankan training dan log semua output ke file + terminal
"""
import subprocess
import sys
import os
from datetime import datetime

def create_log_file():
    """Create timestamped log file"""
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    log_file = f"training_log_{timestamp}.txt"
    return log_file

def run_with_logging(command, log_file):
    """Run command and log output to both file and console"""
    print(f"\n{'='*70}")
    print(f"[TRAINING MONITOR] Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"[LOG FILE] {log_file}")
    print(f"{'='*70}\n")
    
    try:
        with open(log_file, 'w', encoding='utf-8') as f:
            # Write initial info
            f.write(f"Training Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"Command: {' '.join(command)}\n")
            f.write(f"{'='*70}\n\n")
            f.flush()
            
            # Run process with UTF-8 encoding for subprocess output
            process = subprocess.Popen(
                command,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                text=True,
                bufsize=1,
                universal_newlines=True,
                encoding='utf-8',
                errors='replace'  # Replace problematic characters
            )
            
            # Capture output line by line
            for line in iter(process.stdout.readline, ''):
                if line:
                    # Print to console (with error handling for emoji)
                    try:
                        print(line, end='', flush=True)
                    except UnicodeEncodeError:
                        # Fallback: print without emoji
                        clean_line = line.encode('ascii', errors='replace').decode('ascii')
                        print(clean_line, end='', flush=True)
                    
                    # Write to file (UTF-8 compatible)
                    f.write(line)
                    f.flush()
            
            # Wait for process to finish
            return_code = process.wait()
            
            # Final summary
            end_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            summary = f"\n{'='*70}\n[COMPLETED] {end_time}\nExit Code: {return_code}\n{'='*70}\n"
            print(summary)
            f.write(summary)
            
            return return_code
            
    except KeyboardInterrupt:
        error_msg = "\n[ERROR] Training Interrupted by User\n"
        print(error_msg)
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(error_msg)
        sys.exit(1)
    except Exception as e:
        error_msg = f"\n[ERROR] {str(e)}\n"
        print(error_msg)
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(error_msg)
        sys.exit(1)

if __name__ == "__main__":
    # Get epochs from command line or default to 3
    epochs = sys.argv[1] if len(sys.argv) > 1 else "3"
    
    # Create log file
    log_file = create_log_file()
    
    # Build command
    command = [sys.executable, "run_complete_pipeline.py", "--epochs", epochs]
    
    # Run with logging
    exit_code = run_with_logging(command, log_file)
    
    # Final message
    print(f"\n[LOG SAVED] {log_file}")
    print(f"[INFO] Open log file for complete details")
    
    sys.exit(exit_code)
