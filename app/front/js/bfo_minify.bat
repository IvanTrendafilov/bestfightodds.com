java -jar compiler.jar --compilation_level ADVANCED_OPTIMIZATIONS --externs extern-jquery-1.9.js --externs extern-custom-bfo.js --js bfo_main.js --js_output_file bfo_main_optimized.js 
java -jar compiler.jar --compilation_level SIMPLE_OPTIMIZATIONS --js bfo_charts.js --js_output_file bfo_charts_optimized.js
