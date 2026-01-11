#!/usr/bin/env python3
import json
import os
import sys
from flask import Flask, request, jsonify
from flask_cors import CORS
import argostranslate.package
import argostranslate.translate

app = Flask(__name__)
CORS(app)

LANGUAGE_MAP = {'en': 'en', 'fil': 'tl', 'tl': 'tl', 'es': 'es', 'fr': 'fr', 'de': 'de'}

def normalize_language_code(lang_code):
    return LANGUAGE_MAP.get(lang_code.lower().strip(), lang_code.lower().strip())

def ensure_package_installed(from_code, to_code):
    try:
        installed = argostranslate.package.get_installed_packages()
        for pkg in installed:
            if (pkg.from_code == from_code and pkg.to_code == to_code) or (pkg.from_code == to_code and pkg.to_code == from_code):
                return True
        available = argostranslate.package.get_available_packages()
        for pkg in available:
            if pkg.from_code == from_code and pkg.to_code == to_code:
                argostranslate.package.install_from_path(pkg.download())
                return True
        return False
    except:
        return False

@app.route('/translate', methods=['POST'])
def translate():
    try:
        data = request.get_json()
        if not data:
            return jsonify({'success': False, 'error': 'No JSON data provided'}), 400
        text = data.get('q') or data.get('text', '')
        source_lang = normalize_language_code(data.get('source', 'en'))
        target_lang = normalize_language_code(data.get('target', 'en'))
        if not text:
            return jsonify({'success': False, 'error': 'No text provided'}), 400
        if source_lang == target_lang:
            return jsonify({'success': True, 'translatedText': text, 'source': source_lang, 'target': target_lang})
        if not ensure_package_installed(source_lang, target_lang):
            return jsonify({'success': False, 'error': f'Translation package not available for {source_lang} to {target_lang}'}), 400
        translated_text = argostranslate.translate.translate(text, source_lang, target_lang)
        return jsonify({'success': True, 'translatedText': translated_text, 'source': source_lang, 'target': target_lang})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'success': True, 'status': 'healthy', 'service': 'argos-translate'})

if __name__ == '__main__':
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 5001
    use_production = os.environ.get('USE_PRODUCTION_SERVER', 'false').lower() == 'true'
    if use_production:
        try:
            from waitress import serve
            print(f'Starting Argos Translate Service in PRODUCTION mode on port {port}')
            print('Using Waitress WSGI server')
            serve(app, host='0.0.0.0', port=port, threads=4)
        except ImportError:
            print('Warning: Waitress not installed. Falling back to development server.')
            app.run(host='0.0.0.0', port=port, debug=False)
    else:
        print('WARNING: Running in DEVELOPMENT mode')
        app.run(host='0.0.0.0', port=port, debug=False)
