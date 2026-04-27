#!/usr/bin/env python3
"""
CSS Variables Analyzer for Tabik Project
Analizes tokens.css and finds unused CSS variables across the project.
"""

import os
import re
import json
from collections import defaultdict
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import argparse


class CSSVariableAnalyzer:
    def __init__(self, root_dir="public_html", tokens_file="public_html/assets/css/tokens.css"):
        self.root_dir = root_dir
        self.tokens_file = tokens_file
        self.variables = {}  # {variable_name: value}
        self.usage = defaultdict(list)  # {variable_name: [file_path, ...]}
        self.unused = []
        self.used = []
        
    def extract_css_variables(self):
        """Extract all CSS variables from tokens.css"""
        print(f"Extracting CSS variables from {self.tokens_file}...")
        self.variables = {}
        
        with open(self.tokens_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Match CSS variable definitions: --variable-name: value;
        pattern = r'(--[\w-]+)\s*:\s*([^;]+);'
        matches = re.findall(pattern, content)
        
        for var_name, value in matches:
            self.variables[var_name] = value.strip()
        
        print(f"Found {len(self.variables)} CSS variables in tokens.css")
        return self.variables
    
    def search_file(self, file_path):
        """Search for CSS variable usage in a single file"""
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            for var_name in self.variables.keys():
                # Match var(--variable-name) usage
                pattern = rf'var\(\s*{re.escape(var_name)}\s*\)'
                if re.search(pattern, content):
                    self.usage[var_name].append(file_path)
        except Exception as e:
            print(f"Error processing {file_path}: {e}")
    
    def search_directory(self):
        """Search for CSS variable usage across all files in the directory"""
        print(f"Searching for variable usage in {self.root_dir}...")
        
        # File extensions to search
        extensions = {'.css', '.js', '.php', '.html', '.jsx', '.tsx', '.vue'}
        
        files_to_search = []
        for root, dirs, files in os.walk(self.root_dir):
            # Skip node_modules and other non-source directories
            dirs[:] = [d for d in dirs if d not in {'.git', 'node_modules', '__pycache__', 'venv', 'dist', 'build'}]
            
            for file in files:
                if Path(file).suffix in extensions:
                    files_to_search.append(os.path.join(root, file))
        
        print(f"Found {len(files_to_search)} files to search")
        
        # Use ThreadPoolExecutor for parallel processing
        with ThreadPoolExecutor(max_workers=4) as executor:
            list(executor.map(self.search_file, files_to_search))
        
        print(f"Search complete")
    
    def analyze_usage(self):
        """Analyze which variables are used and which are not"""
        print("Analyzing variable usage...")
        
        self.used = []
        self.unused = []
        
        for var_name in self.variables.keys():
            if var_name in self.usage and self.usage[var_name]:
                self.used.append(var_name)
            else:
                self.unused.append(var_name)
        
        print(f"Found {len(self.used)} used variables")
        print(f"Found {len(self.unused)} unused variables")
        
        return self.used, self.unused
    
    def generate_reports(self, output_dir="css_analysis_reports"):
        """Generate detailed reports about variable usage"""
        print(f"Generating reports in {output_dir}...")
        
        os.makedirs(output_dir, exist_ok=True)
        
        # 1. Detailed JSON report
        detailed_report = {
            "summary": {
                "total_variables": len(self.variables),
                "used_variables": len(self.used),
                "unused_variables": len(self.unused),
                "unused_list": self.unused
            },
            "detailed_usage": {}
        }
        
        for var_name in self.variables.keys():
            detailed_report["detailed_usage"][var_name] = {
                "value": self.variables[var_name],
                "is_used": var_name in self.used,
                "used_in_files": self.usage.get(var_name, [])
            }
        
        # Save JSON report
        json_path = os.path.join(output_dir, "detailed_report.json")
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump(detailed_report, f, indent=2, ensure_ascii=False)
        print(f"Saved detailed JSON report: {json_path}")
        
        # 2. Human-readable text report
        text_report = []
        text_report.append("=" * 80)
        text_report.append("CSS VARIABLES ANALYSIS REPORT")
        text_report.append("=" * 80)
        text_report.append(f"Project: Tabik")
        text_report.append(f"Tokens File: {self.tokens_file}")
        text_report.append("-" * 80)
        text_report.append(f"Total Variables Found: {len(self.variables)}")
        text_report.append(f"Variables Used: {len(self.used)}")
        text_report.append(f"Variables Unused: {len(self.unused)}")
        text_report.append("-" * 80)
        text_report.append("")
        
        # Unused variables section
        text_report.append("UNUSED VARIABLES (CAN BE REMOVED)")
        text_report.append("-" * 80)
        if self.unused:
            for var_name in sorted(self.unused):
                text_report.append(f"  {var_name}: {self.variables[var_name]}")
        else:
            text_report.append("  (none)")
        text_report.append("")
        
        # Used variables section
        text_report.append("USED VARIABLES (KEEP THESE)")
        text_report.append("-" * 80)
        for var_name in sorted(self.used):
            files_count = len(self.usage.get(var_name, []))
            text_report.append(f"  {var_name}: {self.variables[var_name]} (used in {files_count} files)")
        text_report.append("")
        text_report.append("=" * 80)
        
        # Save text report
        text_path = os.path.join(output_dir, "summary_report.txt")
        with open(text_path, 'w', encoding='utf-8') as f:
            f.write('\n'.join(text_report))
        print(f"Saved summary text report: {text_path}")
        
        return detailed_report, text_report
    
    def clean_unused(self):
        """Remove unused variables from tokens.css (optional cleanup)"""
        print("Cleaning unused variables from tokens.css...")
        
        if not self.unused:
            print("No unused variables to clean.")
            return
        
        # Read tokens.css
        with open(self.tokens_file, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        # Remove unused variables
        cleaned_lines = []
        for line in lines:
            should_keep = True
            for var_name in self.unused:
                # Check if this line defines an unused variable
                pattern = rf'^\s*{re.escape(var_name)}\s*:'
                if re.match(pattern, line):
                    should_keep = False
                    break
            if should_keep:
                cleaned_lines.append(line)
        
        # Save backup
        backup_file = self.tokens_file + ".backup"
        with open(backup_file, 'w', encoding='utf-8') as f:
            f.writelines(lines)
        print(f"Backup created: {backup_file}")
        
        # Save cleaned tokens.css
        with open(self.tokens_file, 'w', encoding='utf-8') as f:
            f.writelines(cleaned_lines)
        
        print(f"Cleaned tokens.css saved")
        print(f"Removed {len(self.unused)} unused variables")


def main():
    parser = argparse.ArgumentParser(description='CSS Variables Analyzer for Tabik Project')
    parser.add_argument('--root', default='public_html', help='Root directory to search')
    parser.add_argument('--tokens', default='public_html/assets/css/tokens.css', help='Tokens CSS file')
    parser.add_argument('--output', default='css_analysis_reports', help='Output directory for reports')
    parser.add_argument('--clean', action='store_true', help='Clean unused variables from tokens.css')
    
    args = parser.parse_args()
    
    analyzer = CSSVariableAnalyzer(root_dir=args.root, tokens_file=args.tokens)
    
    # Step 1: Extract variables
    analyzer.extract_css_variables()
    
    # Step 2: Search for usage
    analyzer.search_directory()
    
    # Step 3: Analyze usage
    analyzer.analyze_usage()
    
    # Step 4: Generate reports
    analyzer.generate_reports(output_dir=args.output)
    
    # Step 5: Clean unused variables (optional)
    if args.clean:
        print("\nCleaning unused variables...")
        analyzer.clean_unused()
    
    print("\n" + "=" * 80)
    print("ANALYSIS COMPLETE!")
    print("=" * 80)


if __name__ == "__main__":
    main()
