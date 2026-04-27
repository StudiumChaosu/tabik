#!/usr/bin/env python3
"""
CSS Classes Analyzer for Tabik Project
Analyzes glowny.css and finds unused CSS classes across the project.
"""

import os
import re
import json
from collections import defaultdict
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import argparse


class CSSClassAnalyzer:
    def __init__(self, root_dir="public_html", css_file="public_html/assets/css/glowny.css"):
        self.root_dir = root_dir
        self.css_file = css_file
        self.classes = {}  # {class_name: css_rule}
        self.usage = defaultdict(list)  # {class_name: [file_path, ...]}
        self.unused = []
        self.used = []
        
    def extract_css_classes(self):
        """Extract all CSS classes from the CSS file"""
        print(f"Extracting CSS classes from {self.css_file}...")
        self.classes = {}
        
        with open(self.css_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Remove comments
        content = re.sub(r'/\*.*?\*/', '', content, flags=re.DOTALL)
        
        # Match CSS class definitions
        # Pattern matches: .class-name, .class-name:hover, .class-name.another-class, etc.
        pattern = r'\.([a-zA-Z0-9_-]+)(?:[:\s,\.\[>+~]|$)'
        matches = re.findall(pattern, content)
        
        # Get unique classes
        unique_classes = set(matches)
        
        # Store classes with their context (for reporting)
        for class_name in unique_classes:
            # Find the rule containing this class
            rule_pattern = rf'\.{re.escape(class_name)}[^{{]*\{{[^}}]*\}}'
            rule_match = re.search(rule_pattern, content, re.DOTALL)
            if rule_match:
                self.classes[class_name] = rule_match.group(0)[:200]  # First 200 chars
            else:
                self.classes[class_name] = f".{class_name} {{ ... }}"
        
        print(f"Found {len(self.classes)} unique CSS classes in {self.css_file}")
        return self.classes
    
    def search_file(self, file_path):
        """Search for CSS class usage in a single file"""
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            for class_name in self.classes.keys():
                # Match class usage in HTML/PHP: class="...", class='...', classList operations
                # Also match in JS: classList.add/remove/toggle/contains
                patterns = [
                    rf'class\s*=\s*["\'][^"\']*\b{re.escape(class_name)}\b[^"\']*["\']',
                    rf'classList\.(add|remove|toggle|contains)\s*\(\s*["\']?{re.escape(class_name)}["\']?\s*\)',
                    rf'className\s*=\s*["\'][^"\']*\b{re.escape(class_name)}\b[^"\']*["\']',
                    rf'\.{re.escape(class_name)}\b',  # Direct class reference in JS/CSS
                ]
                
                for pattern in patterns:
                    if re.search(pattern, content):
                        self.usage[class_name].append(file_path)
                        break  # Found in this file, no need to check other patterns
        except Exception as e:
            print(f"Error processing {file_path}: {e}")
    
    def search_directory(self):
        """Search for CSS class usage across all files in the directory"""
        print(f"Searching for class usage in {self.root_dir}...")
        
        # File extensions to search
        extensions = {'.css', '.js', '.php', '.html', '.jsx', '.tsx', '.vue'}
        
        files_to_search = []
        for root, dirs, files in os.walk(self.root_dir):
            # Skip non-source directories
            dirs[:] = [d for d in dirs if d not in {'.git', 'node_modules', '__pycache__', 'venv', 'dist', 'build', 'uploads'}]
            
            for file in files:
                if Path(file).suffix in extensions:
                    file_path = os.path.join(root, file)
                    # Skip the CSS file itself
                    if os.path.abspath(file_path) != os.path.abspath(self.css_file):
                        files_to_search.append(file_path)
        
        print(f"Found {len(files_to_search)} files to search")
        
        # Use ThreadPoolExecutor for parallel processing
        with ThreadPoolExecutor(max_workers=4) as executor:
            list(executor.map(self.search_file, files_to_search))
        
        print(f"Search complete")
    
    def analyze_usage(self):
        """Analyze which classes are used and which are not"""
        print("Analyzing class usage...")
        
        self.used = []
        self.unused = []
        
        for class_name in self.classes.keys():
            if class_name in self.usage and self.usage[class_name]:
                self.used.append(class_name)
            else:
                self.unused.append(class_name)
        
        print(f"Found {len(self.used)} used classes")
        print(f"Found {len(self.unused)} unused classes")
        
        return self.used, self.unused
    
    def generate_reports(self, output_dir="css_classes_analysis"):
        """Generate detailed reports about class usage"""
        print(f"Generating reports in {output_dir}...")
        
        os.makedirs(output_dir, exist_ok=True)
        
        # 1. Detailed JSON report
        detailed_report = {
            "summary": {
                "css_file": self.css_file,
                "total_classes": len(self.classes),
                "used_classes": len(self.used),
                "unused_classes": len(self.unused),
                "unused_percentage": round((len(self.unused) / len(self.classes) * 100) if self.classes else 0, 2),
                "unused_list": sorted(self.unused)
            },
            "detailed_usage": {}
        }
        
        for class_name in sorted(self.classes.keys()):
            detailed_report["detailed_usage"][class_name] = {
                "css_rule": self.classes[class_name],
                "is_used": class_name in self.used,
                "used_in_files": self.usage.get(class_name, []),
                "usage_count": len(self.usage.get(class_name, []))
            }
        
        # Save JSON report
        json_path = os.path.join(output_dir, "detailed_report.json")
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump(detailed_report, f, indent=2, ensure_ascii=False)
        print(f"Saved detailed JSON report: {json_path}")
        
        # 2. Human-readable text report
        text_report = []
        text_report.append("=" * 80)
        text_report.append("CSS CLASSES ANALYSIS REPORT")
        text_report.append("=" * 80)
        text_report.append(f"Project: Tabik")
        text_report.append(f"CSS File: {self.css_file}")
        text_report.append("-" * 80)
        text_report.append(f"Total Classes Found: {len(self.classes)}")
        text_report.append(f"Classes Used: {len(self.used)}")
        text_report.append(f"Classes Unused: {len(self.unused)}")
        text_report.append(f"Unused Percentage: {detailed_report['summary']['unused_percentage']}%")
        text_report.append("-" * 80)
        text_report.append("")
        
        # Unused classes section
        text_report.append("UNUSED CLASSES (CAN BE REMOVED)")
        text_report.append("-" * 80)
        if self.unused:
            for class_name in sorted(self.unused):
                text_report.append(f"  .{class_name}")
        else:
            text_report.append("  (none)")
        text_report.append("")
        
        # Used classes section
        text_report.append("USED CLASSES (KEEP THESE)")
        text_report.append("-" * 80)
        for class_name in sorted(self.used):
            files_count = len(self.usage.get(class_name, []))
            text_report.append(f"  .{class_name} (used in {files_count} files)")
        text_report.append("")
        
        # Most used classes
        text_report.append("TOP 10 MOST USED CLASSES")
        text_report.append("-" * 80)
        usage_sorted = sorted([(c, len(self.usage.get(c, []))) for c in self.used], key=lambda x: x[1], reverse=True)
        for class_name, count in usage_sorted[:10]:
            text_report.append(f"  .{class_name}: {count} files")
        text_report.append("")
        
        text_report.append("=" * 80)
        
        # Save text report
        text_path = os.path.join(output_dir, "summary_report.txt")
        with open(text_path, 'w', encoding='utf-8') as f:
            f.write('\n'.join(text_report))
        print(f"Saved summary text report: {text_path}")
        
        return detailed_report, text_report
    
    def clean_unused(self):
        """Remove unused classes from CSS file (optional cleanup)"""
        print("Cleaning unused classes from CSS file...")
        
        if not self.unused:
            print("No unused classes to clean.")
            return
        
        # Read CSS file
        with open(self.css_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Save backup
        backup_file = self.css_file + ".backup"
        with open(backup_file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Backup created: {backup_file}")
        
        # Remove unused class rules
        cleaned_content = content
        removed_count = 0
        
        for class_name in self.unused:
            # Pattern to match the entire CSS rule for this class
            # This is a simplified approach - may need refinement for complex selectors
            pattern = rf'\.{re.escape(class_name)}[^{{]*\{{[^}}]*\}}'
            matches = re.findall(pattern, cleaned_content, re.DOTALL)
            if matches:
                for match in matches:
                    cleaned_content = cleaned_content.replace(match, '')
                    removed_count += 1
        
        # Clean up multiple empty lines
        cleaned_content = re.sub(r'\n{3,}', '\n\n', cleaned_content)
        
        # Save cleaned CSS file
        with open(self.css_file, 'w', encoding='utf-8') as f:
            f.write(cleaned_content)
        
        print(f"Cleaned CSS file saved")
        print(f"Removed {removed_count} CSS rules for {len(self.unused)} unused classes")


def main():
    parser = argparse.ArgumentParser(description='CSS Classes Analyzer for Tabik Project')
    parser.add_argument('--root', default='public_html', help='Root directory to search')
    parser.add_argument('--css', default='public_html/assets/css/glowny.css', help='CSS file to analyze')
    parser.add_argument('--output', default='css_classes_analysis', help='Output directory for reports')
    parser.add_argument('--clean', action='store_true', help='Clean unused classes from CSS file')
    
    args = parser.parse_args()
    
    analyzer = CSSClassAnalyzer(root_dir=args.root, css_file=args.css)
    
    # Step 1: Extract classes
    analyzer.extract_css_classes()
    
    # Step 2: Search for usage
    analyzer.search_directory()
    
    # Step 3: Analyze usage
    analyzer.analyze_usage()
    
    # Step 4: Generate reports
    analyzer.generate_reports(output_dir=args.output)
    
    # Step 5: Clean unused classes (optional)
    if args.clean:
        print("\nCleaning unused classes...")
        analyzer.clean_unused()
    
    print("\n" + "=" * 80)
    print("ANALYSIS COMPLETE!")
    print("=" * 80)


if __name__ == "__main__":
    main()
